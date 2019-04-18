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

use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoRevision\helper\CloneHelper;
use oat\generis\model\data\ModelManager;
use oat\taoRevision\helper\DeleteHelper;
use oat\oatbox\service\ConfigurableService;

/**
 * A simple repository implementation that stores the information
 * in a dedicated rds table and all related files at separate FS
 *
 * @author bout
 */
class RepositoryService extends ConfigurableService implements Repository
{
    const OPTION_STORAGE = 'storage';
    const OPTION_FS = 'filesystem';

    private $storage;

    /**
     * @var FileSystem
     */
    private $fileSystem;

    /**
     * @return RevisionStorage
     */
    protected function getStorage()
    {
        if(is_null($this->storage)) {
            $this->storage = $this->getServiceLocator()->get($this->getOption(self::OPTION_STORAGE));
        }
        return $this->storage;
    }

    /**
     *
     * @return FileSystem
     */
    protected function getFileSystem()
    {
        if (is_null($this->fileSystem)) {
            $this->fileSystem = $this->getServiceLocator()->get(FileSystemService::SERVICE_ID)->getFileSystem($this->getOption(self::OPTION_FS));
        }
        return $this->fileSystem;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoRevision\model\Repository::getRevisions()
     */
    public function getRevisions($resourceId)
    {
        return $this->getStorage()->getAllRevisions($resourceId);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoRevision\model\Repository::getRevision()
     */
    public function getRevision($resourceId, $version)
    {
        return $this->getStorage()->getRevision($resourceId, $version);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoRevision\model\Repository::commit()
     */
    public function commit($resourceId, $message, $version = null, $userId = null)
    {
        if ($userId === null) {
            $user = \common_session_SessionManager::getSession()->getUser();
            $userId = ($user === null) ? '' : $user->getIdentifier();
        }
        $version = is_null($version) ? $this->getNextVersion($resourceId) : $version;
        $created = time();

        // save data
        $resource = new \core_kernel_classes_Resource($resourceId);
        $triples = $resource->getRdfTriples();

        $filesystemMap = array_fill_keys(array_keys(CloneHelper::getPropertyStorageMap($triples)),
            $this->getFileSystem()->getId());

        $data = CloneHelper::deepCloneTriples($triples, $filesystemMap);

        $revision = $this->getStorage()->addRevision($resourceId, $version, $created, $userId, $message, $data);

        return $revision;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoRevision\model\Repository::restore()
     */
    public function restore(Revision $revision) {
        $resourceId = $revision->getResourceId();
        $data = $this->getStorage()->getData($revision);

        $resource = new \core_kernel_classes_Resource($resourceId);
        $originFilesystemMap = CloneHelper::getPropertyStorageMap($resource->getRdfTriples());
        DeleteHelper::deepDelete($resource);

        foreach (CloneHelper::deepCloneTriples($data, $originFilesystemMap) as $triple) {
            ModelManager::getModel()->getRdfInterface()->add($triple);
        }

        return true;
    }

    /**
     * Helper to determin suitable next version nr
     *
     * @param string $resourceId
     * @return number
     */
    protected function getNextVersion($resourceId) {
        $candidate = 0;
        foreach ($this->getRevisions($resourceId) as $revision) {
            $version = $revision->getVersion();
            if (is_numeric($version) && $version > $candidate) {
                $candidate = $version;
            }
        }
        return $candidate + 1;
    }
}
