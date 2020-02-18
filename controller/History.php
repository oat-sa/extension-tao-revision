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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoRevision\controller;

use common_Exception;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\oatbox\service\ServiceManagerAwareTrait;
use oat\tao\helpers\UserHelper;
use oat\taoRevision\model\RepositoryInterface;
use oat\taoRevision\model\RepositoryService;
use oat\taoRevision\model\Revision;
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
     * @return RepositoryInterface
     * @throws InvalidServiceManagerException
     */
    protected function getRevisionService()
    {
        return $this->getServiceManager()->get(RepositoryInterface::SERVICE_ID);
    }

    /**
     * @throws InvalidServiceManagerException
     * @throws common_Exception
     */
    public function index()
    {
        $resource = $this->getResource($this->getPsrRequest()->getParsedBody()['id']);
        $revisions = $this->getRevisionService()->getAllRevisions($resource->getUri());

        $revisionsList = [];
        /** @var Revision $revision */
        foreach ($revisions as $revision) {
            $revisionsList[] = [
                'id' => $revision->getVersion(),
                'modified' => tao_helpers_Date::displayeDate($revision->getDateCreated()),
                'author' => UserHelper::renderHtmlUser($revision->getAuthorId()),
                'message' => tao_helpers_Display::htmlize($revision->getMessage()),
            ];
        }

        $this->setData('resourceLabel', tao_helpers_Display::htmlize($resource->getLabel()));
        $this->setData('id', $resource->getUri());
        $this->setData('revisions', $revisionsList);
        $this->setView('History/index.tpl');
    }

    /**
     * @throws InvalidServiceManagerException
     * @throws RevisionNotFoundException
     * @throws common_Exception
     */
    public function restoreRevision()
    {
        $resource = $this->getResource($this->getPsrRequest()->getParsedBody()['id']);

        $previousVersion = $this->getPsrRequest()->getParsedBody()['revisionId'];
        $commitMessage = $this->getPsrRequest()->getParsedBody()['message'];
        
        $previousRevision = $this->getRevisionService()->getRevision($resource->getUri(), $previousVersion);

        if ($this->getRevisionService()->restore($previousRevision)) {
            $newRevision = $this->getRevisionService()->commit($resource->getUri(), $commitMessage);
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
     * @throws InvalidServiceManagerException
     * @throws common_Exception
     */
    public function commitResource()
    {
        $resource = $this->getResource($this->getPsrRequest()->getParsedBody()['id']);
        $revision = $this->getRevisionService()->commit($resource->getUri(), $_POST['message'] ?? '');

        $this->returnJson([
            'success' => true,
            'id' => $revision->getVersion(),
            'modified' => tao_helpers_Date::displayeDate($revision->getDateCreated()),
            'author' => UserHelper::renderHtmlUser($revision->getAuthorId()),
            'message' => $revision->getMessage(),
            'commitMessage' => __('%s has been committed', $resource->getLabel()),
        ]);
    }
}
