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

namespace oat\taoRevision\model\rds;

use oat\taoRevision\model\Revision as RevisionInterface;

/**
 * A mock implementation for development
 * 
 * @author bout
 */
class Revision implements RevisionInterface
{
    private $id;
    
    private $resourceId;
    
    private $version;
    
    private $created;
    
    private $author;
    
    private $message;
    
    public function __construct($id, $resourceId, $version, $created, $author, $message) {
        $this->id = $id;
        $this->resourceId = $resourceId;
        $this->version = $version;
        $this->created = $created;
        $this->author = $author;
        $this->message = $message;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getResourceId()
    {
        return $this->resourceId;
    }
    
    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
    
    /**
     * @return int
     */
    public function getDateCreated()
    {
        return $this->created;
    }
    
    /**
     * @return string
     */
    public function getAuthorId()
    {
        return $this->author;
    }
}
