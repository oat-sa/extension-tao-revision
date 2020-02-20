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

namespace oat\taoRevision\test\integration\model;

use common_session_Session;
use common_session_SessionManager;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\test\GenerisTestCase;
use oat\oatbox\service\ConfigurableService;
use oat\taoRevision\model\Revision;
use oat\taoRevision\model\RepositoryService;
use oat\taoRevision\model\RevisionNotFoundException;
use Prophecy\Argument;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Zend\ServiceManager\ServiceLocatorInterface;
use oat\taoRevision\model\RevisionStorageInterface;
use oat\oatbox\user\User;

class RepositoryTest extends GenerisTestCase
{
    /** @var Revision[] */
    private $revisions = [];

    /** @var RepositoryService */
    private $repository;

    public function setUp()
    {
        parent::setUp();

        $this->revisions = [
            new Revision('123', 456, 1582066925, 'Great author', 'My message is really cool'),
            new Revision('123', 789, 1581566925, 'Great author', 'My message is really cool'),
        ];
    }

    public function testGetAllRevisions()
    {
        $storage = $this->getRevisionStorage();
        $storage->getAllRevisions(Argument::type('string'))->shouldBeCalled();

        $repository = $this->getRepositoryService($storage->reveal());

        $revisions = $repository->getAllRevisions('123');

        $this->assertEquals($this->revisions, $revisions);
    }

    public function testGetRevision()
    {
        $revision = $this->revisions[0];

        $storage = $this->getRevisionStorage();
        $storage->getRevision(Argument::type('string'), Argument::type('int'))->shouldBeCalled();

        $repository = $this->getRepositoryService($storage->reveal());

        $returnedRevision = $repository->getRevision($revision->getResourceId(), $revision->getVersion());

        $this->assertInstanceOf(Revision::class, $returnedRevision);
        $this->assertEquals($revision, $returnedRevision);
    }

    public function testNotFoundRevision()
    {
        $revision = $this->revisions[0];

        $storage = $this->getRevisionStorage();
        $storage->getRevision(Argument::type('string'), Argument::type('int'))->willThrow(RevisionNotFoundException::class);

        $repository = $this->getRepositoryService($storage->reveal());

        $this->expectException(RevisionNotFoundException::class);

        $repository->getRevision($revision->getResourceId(), $revision->getVersion());
    }

    public function testCommit()
    {
        $storage = $this->getRevisionStorage();
        $storage->addRevision(Argument::type(Revision::class), Argument::type('array'))->shouldBeCalled();

//        $repository = $this->createPartialMock(RepositoryService::class, ['getStorage', 'getFileSystem']);
//        $repository->method('getStorage')->willReturn($storage);
//        $repository->method('getFileSystem')->willReturn($this->getFileSystemMock());
//
//        $repository->method('getFileSystem')->willReturn($this->getFileSystemMock());


        $repository = $this->getRepositoryService($storage->reveal());

//        $model = $this->getOntologyMock();
//        $model->getServiceLocator();
//        $sl = $this->getServiceLocatorMock();
//        $sl->
//        $repository->setServiceLocator()

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('Great author');

        $session = $this->getMockBuilder(common_session_Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $session->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $ref = new ReflectionProperty(common_session_SessionManager::class, 'session');
        $ref->setAccessible(true);
        $ref->setValue(null, $session);

        $revision = $this->revisions[0];

        $model = $this->getOntologyMock();
        $r = $model->getResource($revision->getResourceId());

        // need to creaye resource
        $returnedRevision = $repository->commit($r, $revision->getMessage(), $revision->getVersion());

        print_r($returnedRevision);

        $this->assertEquals($revision, $returnedRevision);
    }

    public function testRestore()
    {
        $revision = $this->revisions[0];

        $storage = $this->getRevisionStorage();
        $storage->getData(Argument::type(Revision::class))->shouldBeCalled();

        $repository = $this->getRepositoryService($storage->reveal());

        $ref = new ReflectionClass(RepositoryService::class);
        $ontologyProp = $ref->getProperty('ontology');
        $ontologyProp->setAccessible(true);
        $ontologyProp->setValue($repository, $this->getOntologyMock());

        $returnedResult = $repository->restore($revision);

        $this->assertEquals(true, $returnedResult);
    }

    public function testGetNextVersion()
    {
        $revisions = [
            new Revision('123', 1, 1582066925, 'Author', 'Message')
        ];

        $storage = $this->getRevisionStorage();
        $storage->getAllRevisions(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($revisions);

        $repository = $this->getRepositoryService($storage->reveal());

        $getNextVersionMethod = new ReflectionMethod(RepositoryService::class, 'getNextVersion');
        $getNextVersionMethod->setAccessible(true);
        $newVersion = $getNextVersionMethod->invokeArgs($repository, ['123']);

        $this->assertEquals(2, $newVersion);
    }

    public function testSearchRevisionResources()
    {
        // Initialize the expected values
        $triple = new \core_kernel_classes_Triple();
        $triple->modelid = 1;
        $triple->subject = 'http://mock/Uri';
        $data = array(
            $triple
        );

        $storageProphecy = $this->prophesize(RevisionStorageInterface::class);
        $storageProphecy->getRevisionsDataByQuery('test')->willReturn($data);

        $repository = $this->getRepository($storageProphecy->reveal());

        $return = $repository->searchRevisionResources('test');

        $this->assertInstanceOf(\core_kernel_classes_Resource::class, $return[0]);
    }

    /**
     * @param $storage
     *
     * @return RepositoryService
     * @throws ReflectionException
     */
    private function getRepositoryService($storage)
    {
        $repositoryService = new RepositoryService();

        $reflectionClass = new ReflectionClass(RepositoryService::class);
        $reflectionProp = $reflectionClass->getProperty('storage');
        $reflectionProp->setAccessible(true);

        $reflectionProp->setValue($repositoryService, $storage);

        return $repositoryService;
    }

    private function getRevisionStorage()
    {
        $revisionStorageProphecy = $this->prophesize(RevisionStorageInterface::class);
        $revisionStorageProphecy->getRevision(Argument::type('string'), Argument::type('int'))->willReturn($this->revisions[0]);
        $revisionStorageProphecy->getAllRevisions(Argument::type('string'))->willReturn($this->revisions);
        $revisionStorageProphecy->addRevision($this->revisions[0], [])->willReturn($this->revisions[0]);
        $revisionStorageProphecy->getData($this->revisions[0])->willReturn([]);

        return $revisionStorageProphecy;
    }
}
