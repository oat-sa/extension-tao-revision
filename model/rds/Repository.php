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

/**
 * A simple repository implementation that stores the information
 * in a dedicated rds table
 * 
 * @author bout
 */
class Repository extends Configurable implements RepositoryInterface
{
    private $storage;
    
    /**
     * @see \oat\oatbox\Configurable::__construct()
     */
    public function __construct($options = array()) {
        parent::__construct($options);
        $this->storage = new Storage($this->getOption('persistence'));
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\taoRevision\model\Repository::getRevisions()
     */
    public function getRevisions($resourceId)
    {
        return $this->storage->getAllRevisions($resourceId);
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\taoRevision\model\Repository::getRevision()
     */
    public function getRevision($resourceId, $version)
    {
        return $this->storage->getRevision($resourceId, $version);
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
        $data = $resource->getRdfTriples();
        
        //clone files
        foreach ($data as $triple) {
            if ($triple->predicate == 'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemContent') {
                // manually copy item content
                $triple->object = $this->cloneItemContent($triple->object);
            }
        }
        
        $revision = $this->storage->addRevision($resourceId, $version, $created, $userId, $message, $data);
        return $revision;
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\taoRevision\model\Repository::restore()
     */
    public function restore(Revision $revision, $newVersion, $message) {
        $resourceId = $revision->getResourceId();
        $data = $this->storage->getData($revision);
        
        $resource = new \core_kernel_classes_Resource($revision->getResourceId());
        $resource->delete();
        foreach ($data as $triple) {
            if ($triple->predicate == RDF_TYPE) {
                $resource->setType(new \core_kernel_classes_Class($triple->object));
            } else {
                if ($triple->predicate == 'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemContent') {
                    $triple->object = $this->cloneItemContent($triple->object);
                }
                if (empty($triple->lg)) {
                    $resource->setPropertyValue(new \core_kernel_classes_Property($triple->predicate), $triple->object);
                } else {
                    $resource->setPropertyValueByLg(new \core_kernel_classes_Property($triple->predicate), $triple->object, $triple->lg);
                }
    
            }
        }
        return $this->commit($resourceId, $message, $newVersion);
    }
    
    public function cloneItemContent($itemContentUri) {
        $fileNameProp = new core_kernel_classes_Property(PROPERTY_FILE_FILENAME);
        $file = new \core_kernel_versioning_File($itemContentUri);
        $sourceDir = dirname($file->getAbsolutePath());

        $newFile = $file->getRepository()->spawnFile($sourceDir);
        $newFile->editPropertyValues($fileNameProp, $file->getPropertyValues($fileNameProp));
        
        return $newFile->getUri();
    }
}
