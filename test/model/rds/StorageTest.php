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

namespace oat\taoRevision\test\model\rds;


use oat\taoRevision\model\rds\RdsRevision;
use oat\taoRevision\model\rds\Storage;

class StorageTest extends \PHPUnit_Framework_TestCase {


    private $storage = null;
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $persistence = null;

    public function setUp(){

        // get storage mock to mock saveData method
        $this->storage = $this->getMockBuilder('oat\taoRevision\model\rds\Storage')
            ->setMethods(array('saveData'))
            ->disableOriginalConstructor()
            ->getMock();


        // persistence mock
        $this->persistence = $this->getMockBuilder('common_persistence_Persistence')
            ->setMethods(array('insert', 'lastInsertId', 'query', 'fetch', 'rowCount'))
            ->disableOriginalConstructor()
            ->getMock();

        // set the mock as persistence for storage
        $ref = new \ReflectionProperty('oat\taoRevision\model\rds\Storage', 'persistence');
        $ref->setAccessible(true);
        $ref->setValue($this->storage, $this->persistence);

    }

    public function tearDown(){

        $this->storage = null;
    }


    public function testAddRevision(){

        // set the revision we are supposed to get
        $resourceId = 123;
        $version = 456;
        $author = "author";
        $message = "my message";
        $created = time();
        $data = array();

        $revision = new RdsRevision(111, $resourceId, $version, $created, $author, $message);

        // see if we call the persistence insert
        $this->persistence->expects($this->once())
            ->method('insert')
            ->with(Storage::REVISION_TABLE_NAME, array(
                    Storage::REVISION_RESOURCE => $resourceId,
                    Storage::REVISION_VERSION => $version,
                    Storage::REVISION_USER => $author,
                    Storage::REVISION_MESSAGE => $message,
                    Storage::REVISION_CREATED => $created
                ));

        // mock the lastInsertId method
        $this->persistence->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(111);

        $this->storage->expects($this->once())
            ->method('saveData');

        $returnValue = $this->storage->addRevision($resourceId, $version, $created, $author, $message, $data);

        $this->assertEquals($revision, $returnValue);
    }


    public function testGetRevision() {

        // set the revision we are supposed to get
        $resourceId = 123;
        $version = 456;
        $author = "author";
        $message = "my message";
        $created = time();

        $revision = new RdsRevision(111, $resourceId, $version, $created, $author, $message);

        // Variables get from the query string
        $variables = $this->getMockBuilder('\Doctrine\DBAL\Driver\Statement')
            ->disableOriginalConstructor()
            ->getMock();

        $variables->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        // the result of a fetch
        $variable = array(
            Storage::REVISION_ID => 111,
            Storage::REVISION_RESOURCE => $resourceId,
            Storage::REVISION_VERSION => $version,
            Storage::REVISION_CREATED => $created,
            Storage::REVISION_USER => $author,
            Storage::REVISION_MESSAGE => $message,
        );

        $variables->expects($this->once())
            ->method('fetch')
            ->willReturn($variable);

        // Sql to see if the query is right
        $sql = 'SELECT * FROM ' . Storage::REVISION_TABLE_NAME
            .' WHERE (' . Storage::REVISION_RESOURCE . ' = ? AND ' . Storage::REVISION_VERSION. ' = ?)';

        $this->persistence->expects($this->once())
            ->method('query')
            ->with($sql ,array($resourceId, $version))
            ->willReturn($variables);
        $returnValue = $this->storage->getRevision($resourceId, $version);

        $this->assertEquals($revision, $returnValue);

    }


    public function testGetAllRevisions() {

        // set revisions we are supposed to get
        $resourceId = 123;
        $version1 = 456;
        $version2 = 789;
        $author = "author";
        $message1 = "my message";
        $message2 = "my new message";
        $created1 = time();
        $created2 = time();

        $revisions = array(
            new RdsRevision(111, $resourceId, $version1, $created1, $author, $message1),
            new RdsRevision(2222, $resourceId, $version2, $created2, $author, $message2)
        );


        // variables get from the query
        $variables = array(
            array(
                Storage::REVISION_ID => 111,
                Storage::REVISION_RESOURCE => $resourceId,
                Storage::REVISION_VERSION => $version1,
                Storage::REVISION_CREATED => $created1,
                Storage::REVISION_USER => $author,
                Storage::REVISION_MESSAGE => $message1,
            ),
            array(
                Storage::REVISION_ID => 2222,
                Storage::REVISION_RESOURCE => $resourceId,
                Storage::REVISION_VERSION => $version2,
                Storage::REVISION_CREATED => $created2,
                Storage::REVISION_USER => $author,
                Storage::REVISION_MESSAGE => $message2,
            )
        );

        // Sql to see if the query is right
        $sql = 'SELECT * FROM ' . Storage::REVISION_TABLE_NAME.' WHERE ' . Storage::REVISION_RESOURCE . ' = ?';

        $this->persistence->expects($this->once())
            ->method('query')
            ->with($sql ,array($resourceId))
            ->willReturn($variables);
        $returnValue = $this->storage->getAllRevisions($resourceId);

        $this->assertEquals($revisions, $returnValue);

    }


    public function testGetData() {

        // set the revision we are supposed to get
        $resourceId = 123;
        $version = 456;
        $author = "author";
        $message = "my message";
        $created = time();
        $revision = new RdsRevision(111, $resourceId, $version, $created, $author, $message);

        $subject1 = "my first subject";
        $predicate1 = "my first predicate";
        $object1 = "my first object";
        $lg1 = "en-en";

        $subject2 = "my second subject";
        $predicate2 = "my second predicate";
        $object2 = "my second object";
        $lg2 = "fr-fr";


        // mock to get the local namespace and then the model id
        $namespaceManager = $this->getMockBuilder('common_ext_NamespaceManager')
            ->disableOriginalConstructor()
            ->getMock();

        $localModel = $this->getMockBuilder('common_ext_Namespace')
            ->disableOriginalConstructor()
            ->getMock();

        $namespaceManager->expects($this->once())
            ->method('getLocalNamespace')
            ->willReturn($localModel);

        $localModel->expects($this->exactly(2))
            ->method('getModelId')
            ->willReturn(321);

        $ref = new \ReflectionProperty('common_ext_NamespaceManager', 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, $namespaceManager);


        // will provide 2 different data
        $variables = $this->getMockBuilder('\Doctrine\DBAL\Driver\Statement')
            ->disableOriginalConstructor()
            ->getMock();

        $variables->expects($this->exactly(3))
            ->method('fetch')
            ->will($this->onConsecutiveCalls(
                    array(
                        Storage::DATA_SUBJECT => $subject1,
                        Storage::DATA_PREDICATE => $predicate1,
                        Storage::DATA_OBJECT => $object1,
                        Storage::DATA_LANGUAGE => $lg1,
                    ),
                    array(
                        Storage::DATA_SUBJECT => $subject2,
                        Storage::DATA_PREDICATE => $predicate2,
                        Storage::DATA_OBJECT => $object2,
                        Storage::DATA_LANGUAGE => $lg2,
                    )
                ));

        $sql = 'SELECT * FROM '.Storage::DATA_TABLE_NAME.' WHERE '.Storage::DATA_REVISION.' = ?';
        $this->persistence->expects($this->once())
            ->method('query')
            ->with($sql ,array(111))
            ->willReturn($variables);

        // Initialize the expected values
        $triple1 = new \core_kernel_classes_Triple();
        $triple2 = new \core_kernel_classes_Triple();
        $triple1->modelid = 321;
        $triple1->subject = $subject1;
        $triple1->predicate = $predicate1;
        $triple1->object = $object1;
        $triple1->lg = $lg1;
        $triple2->modelid = 321;
        $triple2->subject = $subject2;
        $triple2->predicate = $predicate2;
        $triple2->object = $object2;
        $triple2->lg = $lg2;
        $triples = array($triple1, $triple2);

        $data = $this->storage->getData($revision);

        $this->assertEquals($triples, $data);
    }


}
 