<?php
/**
 * Created by Antoine on 24/02/15
 * at 13:23
 */

namespace oat\taoRevision\test\helper;


use oat\taoRevision\helper\CloneHelper;

class CloneHelperTest extends \PHPUnit_Framework_TestCase {

    public function testDeepCloneTriples(){

        $object = new \core_kernel_classes_Class(TAO_OBJECT_CLASS);
        $subClass = $object->createSubClass("My sub Class test");

        // create a file / put it in item content property
        /** @var \core_kernel_versioning_Repository $repository */
        $repository = \tao_models_classes_FileSourceService::singleton()->addLocalSource("repository test", \tao_helpers_File::createTempDir());

        //see if clone works
        $return = CloneHelper::deepCloneTriples($subClass->getRdfTriples());
        $this->assertEquals($subClass->getRdfTriples()->sequence, $return);

        //see if clone item content works
        $file = new \core_kernel_classes_Class("http://www.tao.lu/Ontologies/generis.rdf#File");
        $class = $file->createInstance("test");

        $rdfsTriple = new \core_kernel_classes_Triple();
        $rdfsTriple->predicate = "http://www.tao.lu/Ontologies/TAOItem.rdf#ItemContent";
        $rdfsTriple->object = $class->getUri();
        $fileNameProp = new \core_kernel_classes_Property(PROPERTY_FILE_FILENAME);
        $class->setPropertyValue(new \core_kernel_classes_Property(PROPERTY_FILE_FILESYSTEM), $repository);
        $class->setPropertyValue(new \core_kernel_classes_Property(PROPERTY_FILE_FILEPATH), 'sample');
        $class->setPropertyValue($fileNameProp, 'test.xml');
        mkdir($repository->getPath().'sample');
        copy(__DIR__.'/sample/test.xml', $repository->getPath().'sample/test.xml');
        copy(__DIR__.'/sample/style.css', $repository->getPath().'sample/style.css');
        $return = CloneHelper::deepCloneTriples(array($rdfsTriple));
        $this->assertNotEquals($rdfsTriple->object, $return[0]->object);
        $this->assertEquals($rdfsTriple->predicate, $return[0]->predicate);
        $this->assertCount(1,$return);
        $returnedFile = new \core_kernel_versioning_File($return[0]->object);
        $this->assertEquals($returnedFile->getPropertyValues($fileNameProp), $class->getPropertyValues($fileNameProp));
        $files = scandir(dirname($returnedFile->getAbsolutePath()));
        $this->assertContains('test.xml',$files);
        $this->assertContains('style.css',$files);



        //see if clone file works
        $rdfsTriple = new \core_kernel_classes_Triple();
        $rdfsTriple->predicate = "http://www.w3.org/1999/02/22-rdf-syntax-ns#value";
        $rdfsTriple->object = $class->getUri();
        $return = CloneHelper::deepCloneTriples(array($rdfsTriple));
        $this->assertNotEquals($rdfsTriple->object, $return[0]->object);
        $this->assertEquals($rdfsTriple->predicate, $return[0]->predicate);
        $this->assertCount(1,$return);

        $subClass->delete(true);
        $class->delete(true);
        $repository->delete(true);
    }


    /**
     * @dataProvider fileProvider
     */
    public function testIsFileReference($isRefProvider, $triple){

        $isRef = CloneHelper::isFileReference($triple);

        $this->assertEquals($isRefProvider, $isRef);
    }

    public function fileProvider(){
        $fileTriple = new \core_kernel_classes_Triple();
        $fileTriple->predicate = "http://www.tao.lu/Ontologies/TAOItem.rdf#ItemContent";

        $file = new \core_kernel_classes_Class("http://www.tao.lu/Ontologies/generis.rdf#File");
        $class = $file->createInstance("test");

        $rdfsTriple = new \core_kernel_classes_Triple();
        $rdfsTriple->predicate = "http://www.w3.org/1999/02/22-rdf-syntax-ns#value";
        $rdfsTriple->object = $class->getUri();

        $rdfsTripleFalse = new \core_kernel_classes_Triple();
        $rdfsTripleFalse->predicate = "http://www.w3.org/1999/02/22-rdf-syntax-ns#value";
        $rdfsTripleFalse->object = $file->getUri();

        $falseTriple = new \core_kernel_classes_Triple();
        $falseTriple->predicate = 'otherPredicate';


        return array(
            array(true, $fileTriple),
            array(true, $rdfsTriple),
            array(false, $rdfsTripleFalse),
            array(false, $falseTriple)
        );
    }

}
 