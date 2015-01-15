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
use oat\taoRevision\model\Revision as RevisionInterface;
use oat\oatbox\Configurable;

/**
 * A mock implementation for development
 * 
 * @author bout
 */
class Repository extends Configurable implements RepositoryInterface
{
    private $storage;
    
    public function __construct($options = array()) {
        parent::__construct($options);
        $this->storage = new Storage($this->getOption('persistence'));
    }
    
    /**
     * 
     * @param string $resourceId
     * @return array return an array of Revision objects
     */
    public function getRevisions($resourceId)
    {
        return $this->storage->getAllRevisions($resourceId);
    }
    
    /**
     * @param string $resourceId
     * @param string $revisionId
     * @return Revision
     */
    public function getRevision($resourceId, $revisionId)
    {
        return $this->storage->getRevision($resourceId, $revisionId);
    }
    
    /**
     * 
     * @param string $resourceId
     * @param string $message
     * @param string $revisionId
     * @return Revision
     */
    public function commit($resourceId, $message, $version)
    {
        $user = \common_session_SessionManager::getSession()->getUser();
        $userId = is_null($user) ? null : $user->getIdentifier();
        $created = time();
        
        // save data
        $data = array();
        
        $revision = $this->storage->addRevision($resourceId, $version, $created, $userId, $message, $data);
        return $revision;
    }
    
    public function restore(RevisionInterface $revision, $newVersion, $message) {
        $resourceId = $revision->getVersion();
        $data = $this->storage->getData($revision);
        
        // restore data
        
        $user = \common_session_SessionManager::getSession()->getUser();
        $userId = is_null($user) ? null : $user->getIdentifier();
        return $this->commit($resourceId, $message, $newVersion);
    }
}
