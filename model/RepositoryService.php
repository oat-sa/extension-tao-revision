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
 *
 */

namespace oat\taoRevision\model;

use common_Exception;
use common_exception_Error;
use common_session_SessionManager;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\exception\InvalidService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\taoRevision\helper\CloneHelper;
use oat\generis\model\data\ModelManager;
use oat\taoRevision\helper\DeleteHelper;
use oat\oatbox\service\ConfigurableService;
use tao_models_classes_FileNotFoundException;

/**
 * A simple repository implementation that stores the information
 * in a dedicated rds table and all related files at separate FS
 *
 * @author bout
 */
class RepositoryService extends ConfigurableService implements RepositoryInterface
{
    use OntologyAwareTrait;

    public const FILE_SYSTEM_NAME = 'revisions';

    public const OPTION_STORAGE = 'storage';
    public const OPTION_FILE_SYSTEM = 'filesystem';

    private $storage;

    /** @var FileSystem */
    private $fileSystem;

    /**
     * @return RevisionStorageInterface
     * @throws InvalidService
     * @throws InvalidServiceManagerException
     */
    protected function getStorage()
    {
        if ($this->storage === null) {
            $this->storage = $this->getSubService(self::OPTION_STORAGE);
        }

        return $this->storage;
    }

    /**
     *
     * @return FileSystem
     */
    protected function getFileSystem()
    {
        if ($this->fileSystem === null) {
            $this->fileSystem = $this->getServiceLocator()->get(FileSystemService::SERVICE_ID)->getFileSystem(
                $this->getOption(self::OPTION_FILE_SYSTEM)
            );
        }

        return $this->fileSystem;
    }

    /**
     * @param string $resourceId
     *
     * @return Revision[]
     * @throws InvalidService
     * @throws InvalidServiceManagerException
     */
    public function getAllRevisions(string $resourceId): array
    {
        return $this->getStorage()->getAllRevisions($resourceId);
    }

    /**
     * @param string $resourceId
     * @param int    $version
     *
     * @return Revision
     * @throws InvalidService
     * @throws InvalidServiceManagerException
     */
    public function getRevision(string $resourceId, int $version): Revision
    {
        return $this->getStorage()->getRevision($resourceId, $version);
    }

    /**
     * @param string      $resourceId
     * @param string      $message
     * @param int|null    $version
     * @param string|null $author
     *
     * @return Revision
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws tao_models_classes_FileNotFoundException
     */
    public function commit(string $resourceId, string $message, int $version = null, string $author = null): Revision
    {
        if ($author === null) {
            $user = common_session_SessionManager::getSession()->getUser();
            $author = ($user === null) ? '' : $user->getIdentifier();
        }

        $version = $version ?? $this->getNextVersion($resourceId);

        $resource = $this->getResource($resourceId);
        $triples = $resource->getRdfTriples();

        $filesystemMap = array_fill_keys(
            array_keys(CloneHelper::getPropertyStorageMap($triples)),
            $this->getFileSystem()->getId()
        );

        $revision = new Revision($resourceId, $version, time(), $author, $message);
        $data = CloneHelper::deepCloneTriples($triples, $filesystemMap);

        return $this->getStorage()->addRevision($revision, $data);
    }

    /**
     * @param Revision $revision
     *
     * @return bool
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws tao_models_classes_FileNotFoundException
     */
    public function restore(Revision $revision): bool
    {
        $resourceId = $revision->getResourceId();
        $data = $this->getStorage()->getData($revision);

        $resource = $this->getResource($resourceId);
        $originFilesystemMap = CloneHelper::getPropertyStorageMap($resource->getRdfTriples());
        DeleteHelper::deepDelete($resource);

        foreach (CloneHelper::deepCloneTriples($data, $originFilesystemMap) as $triple) {
            ModelManager::getModel()->getRdfInterface()->add($triple);
        }

        return true;
    }

    /**
     * @param string $query
     *
     * @return Resource[]
     * @throws InvalidService
     * @throws InvalidServiceManagerException
     */
    public function searchRevisionResources(string $query)
    {
        $data = $this->getStorage()->getRevisionsDataByQuery($query);
        $resources = [];
        foreach ($data as $item) {
            $resources[] = $this->getResource($item->subject);
        }

        return $resources;
    }

    /**
     * Helper to determine suitable next version
     *
     * @param string $resourceId
     *
     * @return int
     * @throws InvalidService
     * @throws InvalidServiceManagerException
     */
    protected function getNextVersion(string $resourceId): int
    {
        $candidate = 0;
        foreach ($this->getAllRevisions($resourceId) as $revision) {
            $version = $revision->getVersion();
            if (is_numeric($version) && $version > $candidate) {
                $candidate = $version;
            }
        }

        return $candidate + 1;
    }
}
