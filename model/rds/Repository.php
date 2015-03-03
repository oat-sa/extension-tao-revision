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

namespace oat\taoRevision\model\rds;

use oat\taoRevision\model\RevisionNotFound;
use oat\taoRevision\model\Repository as RepositoryInterface;
use oat\taoRevision\model\Revision;
use oat\oatbox\Configurable;
use core_kernel_classes_Property;
use oat\taoRevision\helper\CloneHelper;
use oat\generis\model\data\ModelManager;
use oat\taoRevision\helper\DeleteHelper;

/**
 * A simple repository implementation that stores the information
 * in a dedicated rds table
 * 
 * @author bout
 */
class Repository extends Configurable implements RepositoryInterface
{
    private $storage = null;

    public function getStorage(){
        if(is_null($this->storage)){
            $this->storage = new Storage($this->getOption('persistence'));
        }
        return $this->storage;
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
    public function commit($resourceId, $message, $version)
    {
        $user = \common_session_SessionManager::getSession()->getUser();
        $userId = is_null($user) ? null : $user->getIdentifier();
        $created = time();
        
        // save data
        $resource = new \core_kernel_classes_Resource($resourceId);
        $data = CloneHelper::deepCloneTriples($resource->getRdfTriples());
        
        $revision = $this->getStorage()->addRevision($resourceId, $version, $created, $userId, $message, $data);
        return $revision;
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\taoRevision\model\Repository::restore()
     */
    public function restore(Revision $revision, $newVersion, $message) {
        $resourceId = $revision->getResourceId();
        $data = $this->getStorage()->getData($revision);
        
        $resource = new \core_kernel_classes_Resource($revision->getResourceId());
        DeleteHelper::deepDelete($resource);
        
        foreach (CloneHelper::deepCloneTriples($data) as $triple) {
            ModelManager::getModel()->getRdfInterface()->add($triple);
        }
        
        return $this->commit($resourceId, $message, $newVersion);
    }

}
