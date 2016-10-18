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
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ServiceManager;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoRevision\helper\CloneHelper;

class CloneHelperTest extends TaoPhpUnitTestRunner
{

    public function testDeepCloneTriplesSimple()
    {
        $object = new \core_kernel_classes_Class('http://www.tao.lu/Ontologies/TAO.rdf#TAOObject');
        $subClass = $object->createSubClass("My sub Class test");

        //see if clone works
        $return = CloneHelper::deepCloneTriples($subClass->getRdfTriples());
        $this->assertEquals($subClass->getRdfTriples()->sequence, $return);

        $subClass->delete(true);
    }

    public function testDeepCloneTriplesItemContent()
    {

        $this->assertFileExists(__DIR__ . '/sample/test.xml');
        $this->assertFileExists(__DIR__ . '/sample/style.css');

        $dir = $this->getTempDirectory();
        $dir->getFile('/test/test.xml')->put(file_get_contents(__DIR__ . '/sample/test.xml'));
        $dir->getFile('/test/style.css')->put(file_get_contents(__DIR__ . '/sample/style.css'));

        /** @var FileReferenceSerializer $serializer */
        $serializer = ServiceManager::getServiceManager()->get(FileReferenceSerializer::SERVICE_ID);
        $fileUri = $serializer->serialize($dir);

        //see if clone file works
        $rdfsTriple = new \core_kernel_classes_Triple();
        $rdfsTriple->predicate = "http://www.tao.lu/Ontologies/TAOItem.rdf#ItemContent";
        $rdfsTriple->object = $fileUri;
        $return = CloneHelper::deepCloneTriples([$rdfsTriple]);

        $this->assertCount(1, $return);
        $this->assertEquals($rdfsTriple->predicate, $return[0]->predicate);
        $this->assertNotEquals($rdfsTriple->object, $return[0]->object);

        /** @var Directory $dirCopy */
        $dirCopy = $serializer->unserialize($return[0]->object);

        $this->assertTrue($dirCopy->exists());
        $this->assertNotEquals($dir->getPrefix(), $dirCopy->getPrefix());
        $this->assertEquals(2, count($dirCopy->getDirectory('test')->getFlyIterator()->getArrayCopy()));

    }


    public function testDeepCloneTriplesFile()
    {

        $this->assertFileExists(__DIR__ . '/sample/test.xml');

        $dir = $this->getTempDirectory();
        $file = $dir->getFile('/sample/test.xml');
        $file->put(file_get_contents(__DIR__ . '/sample/test.xml'));

        /** @var FileReferenceSerializer $serializer */
        $serializer = ServiceManager::getServiceManager()->get(FileReferenceSerializer::SERVICE_ID);
        $fileUri = $serializer->serialize($file);

        //see if clone file works
        $rdfsTriple = new \core_kernel_classes_Triple();
        $rdfsTriple->predicate = "http://www.w3.org/1999/02/22-rdf-syntax-ns#value";
        $rdfsTriple->object = $fileUri;
        $return = CloneHelper::deepCloneTriples([$rdfsTriple]);

        $this->assertCount(1, $return);
        $this->assertEquals($rdfsTriple->predicate, $return[0]->predicate);
        $this->assertNotEquals($rdfsTriple->object, $return[0]->object);

        /** @var File $fileCopy */
        $fileCopy = $serializer->unserialize($return[0]->object);

        $this->assertTrue($fileCopy->exists());
        $this->assertEquals($file->getSize(), $fileCopy->getSize());
        $this->assertNotEquals($file->getPrefix(), $fileCopy->getPrefix());

        $file->delete(true);
        $fileCopy->delete(true);
    }

    /**
     * @dataProvider fileProvider
     */
    public function testIsFileReference($isRefProvider, $triple){

        $isRef = CloneHelper::isFileReference($triple);

        $this->assertEquals($isRefProvider, $isRef);
    }

    public function testIsFileReferenceResourceRange(){

        $classFile = new \core_kernel_classes_Class("http://www.tao.lu/Ontologies/generis.rdf#File");
        $file = $classFile->createInstance("test");

        $rdfsTriple = new \core_kernel_classes_Triple();
        $rdfsTriple->predicate = "http://www.w3.org/1999/02/22-rdf-syntax-ns#value";
        $rdfsTriple->object = $file->getUri();

        $this->assertTrue(CloneHelper::isFileReference($rdfsTriple));
        $file->delete();
    }

    public function fileProvider(){
        $fileTriple = new \core_kernel_classes_Triple();
        $fileTriple->predicate = "http://www.tao.lu/Ontologies/TAOItem.rdf#ItemContent";

        $rdfsTripleFalse = new \core_kernel_classes_Triple();
        $rdfsTripleFalse->predicate = "http://www.w3.org/1999/02/22-rdf-syntax-ns#value";
        $rdfsTripleFalse->object = "http://www.tao.lu/Ontologies/generis.rdf#File";

        $falseTriple = new \core_kernel_classes_Triple();
        $falseTriple->predicate = 'otherPredicate';

        return array(
            array(true, $fileTriple),
            array(false, $rdfsTripleFalse),
            array(false, $falseTriple)
        );
    }

    public function testGetPropertyMap()
    {
        $this->assertFileExists(__DIR__ . '/sample/test.xml');

        $dir = $this->getTempDirectory();
        $file = $dir->getFile('/sample/test.xml');
        $file->put(file_get_contents(__DIR__ . '/sample/test.xml'));

        /** @var FileReferenceSerializer $serializer */
        $serializer = ServiceManager::getServiceManager()->get(FileReferenceSerializer::SERVICE_ID);
        $fileUri = $serializer->serialize($file);

        //see if clone file works
        $rdfsTriple = new \core_kernel_classes_Triple();
        $rdfsTriple->predicate = "http://www.w3.org/1999/02/22-rdf-syntax-ns#value";
        $rdfsTriple->object = $fileUri;

        $return = CloneHelper::getPropertyStorageMap([$rdfsTriple]);
        $this->assertEquals($dir->getFileSystemId(), reset($return));

    }
}
