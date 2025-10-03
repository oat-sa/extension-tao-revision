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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoRevision\model;

use common_Exception;
use common_Logger;
use oat\generis\model\fileReference\FileReferenceSerializer;
use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use core_kernel_classes_ContainerCollection as TriplesCollection;
use core_kernel_classes_Resource as Resource;
use core_kernel_classes_Triple as Triple;
use core_kernel_persistence_smoothsql_SmoothModel as Model;
use oat\taoMediaManager\model\fileManagement\FileSourceSerializer;
use oat\taoMediaManager\model\MediaService;

/**
 * Class TriplesManagerService
 * @package oat\taoRevision\model
 */
class TriplesManagerService extends ConfigurableService
{
    use OntologyAwareTrait;

    public const SERVICE_ID = 'taoRevision/triples';

    /**
     * @return FileReferenceSerializer
     */
    protected function getFileRefSerializer()
    {
        return $this->getServiceLocator()->get(FileReferenceSerializer::SERVICE_ID);
    }

    /**
     * @param Triple $triple
     */
    public function deleteTripleDependencies(Triple $triple)
    {
        if (!$this->isFileReference($triple)) {
            return;
        }

        $referencer = $this->getFileRefSerializer();
        $this->serializeAsset($triple);
        $source = $referencer->unserialize($triple->object);

        if ($source instanceof Directory) {
            $source->deleteSelf();
        } elseif ($source instanceof File) {
            $source->delete();
        }

        $referencer->cleanUp($triple->object);
    }

    /**
     * @param Resource   $resource
     * @param Model|null $model
     */
    public function deleteTriplesFor(Resource $resource, Model $model = null)
    {
        $triples = $model
            ? $model->getRdfsInterface()->getResourceImplementation()->getRdfTriples($resource)
            : $resource->getRdfTriples();

        foreach ($triples as $triple) {
            $this->deleteTripleDependencies($triple);
        }

        $resource->delete();
    }

    /**
     * @param TriplesCollection $triples
     * @param array             $propertyFilesystemMap
     *
     * @return array
     * @throws common_Exception
     */
    public function cloneTriples(TriplesCollection $triples, array $propertyFilesystemMap = [])
    {
        $clones = [];
        foreach ($triples as $original) {
            $triple = clone $original;
            if ($this->isFileReference($triple)) {
                $targetFileSystem = $propertyFilesystemMap[$triple->predicate] ?? null;
                $this->serializeAsset($triple);
                $clonedFileUri = $this->cloneFile($triple->object, $targetFileSystem);

                $file = $this->getFileRefSerializer()->unserializeFile($clonedFileUri);
                $triple->object = $file->getPrefix();
            }
            $clones[] = $triple;
        }

        return $clones;
    }

    /**
     * @param      $fileUri
     * @param null $targetFileSystemId
     *
     * @return string
     * @throws \common_Exception
     * @throws \tao_models_classes_FileNotFoundException
     */
    protected function cloneFile($fileUri, $targetFileSystemId = null)
    {
        $referencer = $this->getFileRefSerializer();
        $flySystemService = $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);

        $source = $referencer->unserialize($fileUri);
        $targetFileSystemId = !$targetFileSystemId ? $source->getFileSystemId() : $targetFileSystemId;
        $destinationPath = $flySystemService->getDirectory($targetFileSystemId)->getDirectory(uniqid('', true));

        if ($source instanceof Directory) {
            common_Logger::i('clone directory ' . $fileUri);
            foreach ($source->getFlyIterator(Directory::ITERATOR_FILE | Directory::ITERATOR_RECURSIVE) as $file) {
                $destinationPath->getFile($source->getRelPath($file))->write($file->readStream());
            }
            $destination = $destinationPath;
        } elseif ($source instanceof File) {
            common_Logger::i('clone file ' . $fileUri);
            $destination = $destinationPath->getFile($source->getBasename());
            $destination->write($source->readStream());
        }

        return $referencer->serialize($destination);
    }

    /**
     * @param TriplesCollection $triples
     *
     * @return array
     */
    public function getPropertyStorageMap(TriplesCollection $triples)
    {
        $map = [];

        foreach ($triples as $triple) {
            if ($this->isFileReference($triple)) {
                $this->serializeAsset($triple);
                $source = $this->getFileRefSerializer()->unserialize($triple->object);
                $map[$triple->predicate] = $source->getFileSystemId();
            }
        }

        return $map;
    }

    /**
     * @param Triple $triple
     *
     * @return bool
     */
    protected function isFileReference(Triple $triple)
    {
        $property = $this->getProperty($triple->predicate);
        $range = $property->getRange();

        $uri = $property->getUri();

        if ($uri == MediaService::PROPERTY_LINK) {
            return true;
        }

        if ($range === null) {
            return false;
        }

        switch ($range->getUri()) {
            case GenerisRdf::CLASS_GENERIS_FILE:
                return true;
            case OntologyRdfs::RDFS_RESOURCE:
                $object = $this->getResource($triple->object);
                return $object->hasType($this->getClass(GenerisRdf::CLASS_GENERIS_FILE));
            default:
                return false;
        }
    }

    private function serializeAsset(Triple $triple): void
    {
        $this->getFileSourceSerializer()->serialize($triple);
    }

    private function getFileSourceSerializer(): FileSourceSerializer
    {
        return $this->getServiceLocator()->get(FileSourceSerializer::class);
    }
}
