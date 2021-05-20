<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2015-2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoRevision\controller;

use common_Exception;
use common_exception_MissingParameter;
use common_exception_ResourceNotFound;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\oatbox\service\ServiceManagerAwareTrait;
use oat\tao\helpers\UserHelper;
use oat\tao\model\accessControl\PermissionChecker;
use oat\tao\model\accessControl\PermissionCheckerInterface;
use oat\tao\model\resources\ResourceAccessDeniedException;
use oat\taoRevision\model\RepositoryInterface;
use oat\taoRevision\model\RevisionNotFoundException;
use tao_actions_CommonModule;
use tao_helpers_Date;
use tao_helpers_Display;

/**
 * Revision history management controller
 *
 * @author Open Assessment Technologies SA
 * @package taoRevision
 * @license GPL-2.0
 *
 */
class History extends tao_actions_CommonModule
{
    use ServiceManagerAwareTrait;
    use OntologyAwareTrait;

    /**
     * @return ConfigurableService
     * @throws InvalidServiceManagerException
     */
    protected function getRevisionService()
    {
        return $this->getServiceManager()->get(RepositoryInterface::SERVICE_ID);
    }

    /**
     * @requiresRight id WRITE
     *
     * @throws InvalidServiceManagerException
     * @throws common_Exception
     * @throws common_exception_MissingParameter
     * @throws common_exception_ResourceNotFound
     */
    public function index()
    {
        $bodyContent = $this->getPsrRequest()->getParsedBody();

        $resource = $this->getValidatedResource($bodyContent);

        $revisions = $this->getRevisionService()->getAllRevisions($resource->getUri());

        $revisionsList = [];
        foreach ($revisions as $revision) {
            if ($author = $revision->getAuthorId()) {
                $author = UserHelper::renderHtmlUser($revision->getAuthorId());
            }
            $revisionsList[] = [
                'id' => $revision->getVersion(),
                'modified' => tao_helpers_Date::displayeDate($revision->getDateCreated()),
                'author' => $author,
                'message' => tao_helpers_Display::htmlize($revision->getMessage()),
            ];
        }

        $permissionChecker = $this->getPermissionChecker();

        $this->setData('hasReadAccess', $permissionChecker->hasReadAccess($resource->getUri()));
        $this->setData('hasWriteAccess', $permissionChecker->hasWriteAccess($resource->getUri()));
        $this->setData('resourceLabel', tao_helpers_Display::htmlize($resource->getLabel()));
        $this->setData('id', $resource->getUri());
        $this->setData('revisions', $revisionsList);
        $this->setView('History/index.tpl');
    }

    /**
     * @requiresRight id WRITE
     *
     * @throws InvalidServiceManagerException
     * @throws RevisionNotFoundException
     * @throws common_Exception
     * @throws common_exception_MissingParameter
     * @throws common_exception_ResourceNotFound
     */
    public function restoreRevision()
    {
        $bodyContent = $this->getPsrRequest()->getParsedBody();

        $resource = $this->getValidatedResource($bodyContent);
        $previousVersion = $bodyContent['revisionId'] ?? null;
        $commitMessage = $bodyContent['message'] ?? '';

        $this->validateWriteAccess($resource);

        if ($previousVersion === null) {
            throw new common_exception_MissingParameter('revisionId');
        }

        $previousRevision = $this->getRevisionService()->getRevision($resource->getUri(), (int)$previousVersion);

        if ($this->getRevisionService()->restore($previousRevision)) {
            $newRevision = $this->getRevisionService()->commit($resource, $commitMessage);
            $this->returnJson([
                'success' => true,
                'id' => $newRevision->getVersion(),
                'modified' => tao_helpers_Date::displayeDate($newRevision->getDateCreated()),
                'author' => UserHelper::renderHtmlUser($newRevision->getAuthorId()),
                'message' => $newRevision->getMessage(),
            ]);
        } else {
            $this->returnError(__('Unable to restore the selected version'));
        }
    }

    /**
     * @requiresRight id WRITE
     *
     * @throws InvalidServiceManagerException
     * @throws common_Exception
     * @throws common_exception_MissingParameter
     * @throws common_exception_ResourceNotFound
     */
    public function commitResource()
    {
        $bodyContent = $this->getPsrRequest()->getParsedBody();

        $resource = $this->getValidatedResource($bodyContent);
        $message = $bodyContent['message'] ?? '';

        $this->validateWriteAccess($resource);

        $revision = $this->getRevisionService()->commit($resource, $message);

        $this->returnJson([
            'success' => true,
            'id' => $revision->getVersion(),
            'modified' => tao_helpers_Date::displayeDate($revision->getDateCreated()),
            'author' => UserHelper::renderHtmlUser($revision->getAuthorId()),
            'message' => $revision->getMessage(),
            'commitMessage' => __('%s has been committed', $resource->getLabel()),
        ]);
    }

    /**
     * @param array $body
     *
     * @return core_kernel_classes_Resource
     * @throws common_exception_MissingParameter
     * @throws common_exception_ResourceNotFound
     */
    private function getValidatedResource(array $body)
    {
        $id = $body['id'] ?? null;

        if ($id === null) {
            throw new common_exception_MissingParameter('id');
        }

        $resource = $this->getResource($id);

        if (!$resource->exists()) {
            throw new common_exception_ResourceNotFound(sprintf('Resource not found for requested id %s', $id));
        }

        return $resource;
    }

    private function validateWriteAccess(core_kernel_classes_Resource $resource): void
    {
        if (!$this->getPermissionChecker()->hasWriteAccess($resource->getUri())) {
            throw new ResourceAccessDeniedException($resource->getUri());
        }
    }

    private function getPermissionChecker(): PermissionCheckerInterface
    {
        return $this->getServiceLocator()->get(PermissionChecker::class);
    }
}
