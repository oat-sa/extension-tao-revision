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

namespace oat\taoRevision\test\helper;


use oat\generis\model\fileReference\FileReferenceSerializer;
use oat\generis\model\GenerisRdf;
use oat\oatbox\service\ServiceManager;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoRevision\helper\DeleteHelper;

class DeleteHelperTest extends TaoPhpUnitTestRunner
{

    public function testDeepDelete()
    {
        $this->assertFileExists(__DIR__ . '/sample/test.xml');

        list($file, $resource) = $this->getResource('/sample/test.xml');

        $this->assertTrue($file->exists());
        //delete resource

        DeleteHelper::deepDelete($resource);

        $this->assertFalse($file->exists());

    }

    public function testDeepDeleteTriples()
    {
        list($file, $resource) = $this->getResource('/sample/test.xml');

        //delete resource
        DeleteHelper::deepDeleteTriples($resource->getRdfTriples());
        //see if all is deleted
        $this->assertCount(0, $resource->getRdfTriples());
        $this->assertFalse($file->exists());

    }

    /**
     * @return array
     */
    protected function getResource($relPath)
    {
        $dir = $this->getTempDirectory();
        $file = $dir->getFile($relPath);
        $file->put(file_get_contents(__DIR__ . $relPath));

        /** @var FileReferenceSerializer $serializer */
        $serializer = ServiceManager::getServiceManager()->get(FileReferenceSerializer::SERVICE_ID);
        $fileUri = $serializer->serialize($file);

        $class = new \core_kernel_classes_Class('fakeClass');
        $resource = $class->createInstance('fakeInstance');
        $prop = new \core_kernel_classes_Property('fakeProp');
        $prop->setRange(new \core_kernel_classes_Class(GenerisRdf::CLASS_GENERIS_FILE));
        $resource->editPropertyValues($prop,
            new \core_kernel_classes_Resource($fileUri));
        return array($file, $resource);
    }
}
 