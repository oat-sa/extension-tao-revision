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

namespace oat\taoRevision\model\mock;

use oat\taoRevision\model\RevisionNotFound;
use oat\taoRevision\model\Repository as RepositoryInterface;

/**
 * A mock implementation for development
 * 
 * @author bout
 */
class Repository implements RepositoryInterface
{
    /**
     * 
     * @param string $resourceId
     * @return array return an array of Revision objects
     */
    public function getRevisions($resourceId)
    {
        $count = substr($resourceId, strlen($resourceId)-1);
        $revisions = array();
        for ($i = 0; $i < $count; $i++) {
            $revisions[] = new Revision($i.(rand(0, 2) == 0 ? '.alpha' : ''));
        }
        return $revisions;
    }
    
    /**
     * @param string $resourceId
     * @param string $revisionId
     * @return Revision
     * @throws \oat\taoRevision\model\RevisionNotFound
     */
    public function getRevision($resourceId, $revisionId)
    {
        if (is_int($revisionId)) {
            return new Revision($revisionId);
        } else {
            throw new RevisionNotFound($resourceId, $revisionId);
        } 
    }
    
    /**
     * 
     * @param string $resourceId
     * @param string $message
     * @param string $revisionId
     * @return Revision
     */
    public function commit($resourceId, $message, $revisionId = null)
    {
        return new Revision(rand(10, 20).(rand(0, 2) == 0 ? '.beta' : ''));
    }
}
