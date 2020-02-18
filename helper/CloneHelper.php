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

use common_Exception;
use common_exception_Error;
use core_kernel_classes_Class;
use core_kernel_classes_ContainerCollection as TriplesCollection;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use core_kernel_classes_Triple as Triple;
use oat\generis\model\fileReference\FileReferenceSerializer;
use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\FileSystemService;
use tao_models_classes_FileNotFoundException;

class CloneHelper
{
    /**
     * @param TriplesCollection $triples
     * @param array             $propertyFilesystemMap
     *
     * @return array
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws tao_models_classes_FileNotFoundException
     */
    public static function deepCloneTriples(TriplesCollection $triples, array $propertyFilesystemMap = [])
    {
        $clones = [];
        foreach ($triples as $original) {
            $triple = clone $original;
            if (self::isFileReference($triple)) {
                $targetFileSystem = $propertyFilesystemMap[$triple->predicate] ?? null;
                $triple->object = self::cloneFile($triple->object, $targetFileSystem);
            }
            $clones[] = $triple;
        }

        return $clones;
    }

    /**
     * @param Triple $triple
     *
     * @return bool
     * @throws common_exception_Error
     */
    public static function isFileReference(Triple $triple)
    {
        $property = new core_kernel_classes_Property($triple->predicate);
        $range = $property->getRange();
        $rangeUri = $range === null ? '' : $range->getUri();
        switch ($rangeUri) {
            case GenerisRdf::CLASS_GENERIS_FILE:
                return true;
            case OntologyRdfs::RDFS_RESOURCE:
                $object = new core_kernel_classes_Resource($triple->object);
                return $object->hasType(new core_kernel_classes_Class(GenerisRdf::CLASS_GENERIS_FILE));
            default:
                return false;
        }
    }

    /**
     * @param             $fileUri
     * @param string|null $targetFileSystemId
     *
     * @return mixed
     * @throws common_Exception
     * @throws tao_models_classes_FileNotFoundException
     */
    protected static function cloneFile($fileUri, $targetFileSystemId = null)
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
     *
     * @param TriplesCollection $triples
     *
     * @return array
     * @throws common_exception_Error
     */
    public static function getPropertyStorageMap(TriplesCollection $triples)
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
