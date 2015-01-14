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
 * 
 */

namespace oat\taoRevision\model\mock;

use oat\taoRevision\model\Revision as RevisionInterface;

/**
 * A mock implementation for development
 * 
 * @author bout
 */
class Revision implements RevisionInterface
{
    private $id;
    
    public function __construct($id) {
        $this->id = $id;
    }
    
    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return 'Message of '.$this->getVersion();
    }
    
    /**
     * @return int
     */
    public function getDateCreated()
    {
        return time();
    }
    
    /**
     * @return string
     */
    public function getAuthorId()
    {
        // assuming we always have a user
        return \common_session_SessionManager::getSession()->getUser()->getIdentifier();
    }
    
    /**
     * @param string $restoreMessage
     * @return Revision
     */
    public function restore($restoreMessage)
    {
        return new Revision($this->getVersion().'.rev');
    }
}
