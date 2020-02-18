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

namespace oat\taoRevision\helper;

use core_kernel_classes_Resource as Resource;
use core_kernel_classes_Triple as Triple;
use core_kernel_persistence_smoothsql_SmoothModel as Model;
use core_kernel_classes_ContainerCollection as TriplesCollection;
use oat\generis\model\data\ModelManager;
use oat\generis\model\fileReference\FileReferenceSerializer;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ServiceManager;

class DeleteHelper
{
    public static function deepDelete(Resource $resource, Model $model = null)
    {
        $triples = $model
            ? $model->getRdfsInterface()->getResourceImplementation()->getRdfTriples($resource)
            : $resource->getRdfTriples();

        foreach ($triples as $triple) {
            self::deleteDependencies($triple);
        }

        $resource->delete();
    }

    public static function deepDeleteTriples(TriplesCollection $triples)
    {
        $rdf = ModelManager::getModel()->getRdfInterface();
        foreach ($triples as $triple) {
            self::deleteDependencies($triple);
            $rdf->remove($triple);
        }
    }

    protected static function deleteDependencies(Triple $triple)
    {
        if (CloneHelper::isFileReference($triple)) {
            $referencer = ServiceManager::getServiceManager()->get(FileReferenceSerializer::SERVICE_ID);
            $source = $referencer->unserialize($triple->object);
            if ($source instanceof Directory) {
                $source->deleteSelf();
            } elseif ($source instanceof File) {
                $source->delete();
            }
            $referencer->cleanUp($triple->object);
        }
    }
}
