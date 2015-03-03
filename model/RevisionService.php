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

use core_kernel_classes_Resource;
use common_session_SessionManager;
use oat\tao\model\lock\LockManager;
use oat\taoRevision\model\workspace\ApplicableLock;

class RevisionService
{
    /**
     * 
     * @param core_kernel_classes_Resource $resource
     * @param string $message
     * @param string $version
     * @return \oat\taoRevision\model\Revision
     */
    static public function commit(core_kernel_classes_Resource $resource, $message, $version = null) {
        
        $version = is_null($version) ? self::getNextVersion($resource->getUri()) : $version;
        $lockManager = LockManager::getImplementation();
        $locked = false;
        if ($lockManager->isLocked($resource)) {
            $userId = common_session_SessionManager::getSession()->getUser()->getIdentifier();
            if ($lockManager instanceof ApplicableLock) {
                $lockManager->apply($resource, $userId, false);
            }
            $locked = true;
        }
        
        //commit a new revision of the resource
        $revision = RepositoryProxy::commit($resource->getUri(), $message, $version);
        
        if ($locked) {
            $ownerId = common_session_SessionManager::getSession()->getUser()->getIdentifier();
            $lockManager->releaseLock($resource, $ownerId);
        }
        
        return $revision;
    }
    
    /**
     * 
     * @param core_kernel_classes_Resource $resource
     * @param string $oldVersion
     * @param string $message
     * @param string $newVersion
     * @return \oat\taoRevision\model\Revision
     */
    static public function restore(core_kernel_classes_Resource $resource, $oldVersion, $message, $newVersion = null) {
        
        $lockManager = LockManager::getImplementation();
        if ($lockManager->isLocked($resource)) {
            $userId = common_session_SessionManager::getSession()->getUser()->getIdentifier();
            $lockManager->releaseLock($resource, $userId);
        }
        
        $oldRevision = RepositoryProxy::getRevision($resource->getUri(), $oldVersion);
        $success = RepositoryProxy::restore($oldRevision);
        
        if ($success) {
            $newVersion = is_null($newVersion) ? self::getNextVersion($resource->getUri()) : $newVersion;
            $newRevision = RevisionService::commit($resource, $message, $newVersion);
            return $newRevision;
        } else {
            throw \common_exception_Error('Unable to restore version '.$oldVersion.' of resource '.$resource->getUri());
        }
    }
    
    /**
     * Helper to determin suitable next version nr
     * 
     * @param string $resourceId
     * @return number
     */
    static protected function getNextVersion($resourceId) {
        $candidate = 0;
        foreach (RepositoryProxy::getRevisions($resourceId) as $revision) {
            $version = $revision->getVersion();
            if (is_numeric($version) && $version > $candidate) {
                $candidate = $version;
            }
        }
        return $candidate + 1;
    }
}
