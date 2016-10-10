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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 * 
 */

namespace oat\taoRevision\helper;

use oat\generis\model\fileReference\FileReferenceSerializer;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\FileSystemService;

class CloneHelper
{
    /**
     * @param $triples
     * @param array $propertyFilesystemMap
     * @return array
     */
    static public function deepCloneTriples($triples, array $propertyFilesystemMap = [])
    {

        $clones = array();
        foreach ($triples as $original) {
            $triple = clone $original;
            if (self::isFileReference($triple)) {
                $targetFileSystem = isset($propertyFilesystemMap[$triple->predicate]) ? $propertyFilesystemMap[$triple->predicate] : null;
                $triple->object = self::cloneFile($triple->object, $targetFileSystem);
            }
            $clones[] = $triple;
        }
        return $clones;
    }

    /**
     * @param \core_kernel_classes_Triple $triple
     * @return bool
     */
    static public function isFileReference(\core_kernel_classes_Triple $triple) {
        $prop = new \core_kernel_classes_Property($triple->predicate);
        $range = $prop->getRange();
        $rangeUri = is_null($range) ? '' : $range->getUri(); 
        switch ($rangeUri) {
        	case CLASS_GENERIS_FILE :
        	    return true;
        	case RDFS_RESOURCE :
        	    $object = new \core_kernel_classes_Resource($triple->object);
        	    return $object->hasType(new \core_kernel_classes_Class(CLASS_GENERIS_FILE));
        	default :
        	    return false;
        }
    }

    /**
     * @param $fileUri
     * @param string|null $targetFileSystemId
     * @return mixed
     */
    static protected function cloneFile($fileUri, $targetFileSystemId = null)
    {
        $referencer = ServiceManager::getServiceManager()->get(FileReferenceSerializer::SERVICE_ID);
        $flySystemService = ServiceManager::getServiceManager()->get(FileSystemService::SERVICE_ID);

        $source = $referencer->unserialize($fileUri);
        $targetFileSystemId = !$targetFileSystemId ? $source->getFileSystemId() : $targetFileSystemId;
        $destinationPath = $flySystemService->getDirectory($targetFileSystemId)->getDirectory(uniqid());

        if ($source instanceof Directory) {
            \common_Logger::i('clone directory ' . $fileUri);
            foreach ($source->getFlyIterator(Directory::ITERATOR_FILE | Directory::ITERATOR_RECURSIVE) as $file) {
                $destinationPath->getFile($source->getRelPath($file))->write($file->readStream());
            }
            $destination = $destinationPath;

        } elseif ($source instanceof File) {
            \common_Logger::i('clone file ' . $fileUri);
            $destination = $destinationPath->getFile($source->getBasename());
            $destination->write($source->readStream());
        }

        return $referencer->serialize($destination);
    }


    /**
     * Determines origins of stored files
     * @param $triples
     * @return array
     */
    static public function getPropertyStorageMap($triples)
    {
        $referencer = ServiceManager::getServiceManager()->get(FileReferenceSerializer::SERVICE_ID);
        $map = [];
        foreach ($triples as $triple) {
            if (self::isFileReference($triple)) {
                $source = $referencer->unserialize($triple->object);
                $map[$triple->predicate] = $source->getFileSystemId();
            }
        }
        return $map;
    }
}
