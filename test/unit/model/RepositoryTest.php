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

namespace oat\taoRevision\test\unit\model;

use common_session_Session;
use common_session_SessionManager;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\model\data\RdfInterface;
use oat\generis\test\GenerisTestCase;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoRevision\model\Revision;
use oat\taoRevision\model\RepositoryService;
use oat\taoRevision\model\RevisionNotFoundException;
use oat\taoRevision\model\TriplesManagerService;
use Prophecy\Argument;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use oat\taoRevision\model\RevisionStorageInterface;
use oat\oatbox\user\User;

class RepositoryTest extends GenerisTestCase
{
    use TriplesMockTrait;

    /** @var Revision[] */
    private $revisions = [];

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
        $storage = $this->prophesize(RevisionStorageInterface::class);
        $storage->getAllRevisions(Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($this->revisions);

        $repository = $this->getRepositoryService($storage->reveal());

        $revisions = $repository->getAllRevisions('123');

        $this->assertEquals($this->revisions, $revisions);
    }

    public function testGetRevision()
    {
        $revision = $this->revisions[0];

        $storage = $this->prophesize(RevisionStorageInterface::class);
        $storage->getRevision(Argument::type('string'), Argument::type('int'))
            ->shouldBeCalled()
            ->willReturn($revision);

        $repository = $this->getRepositoryService($storage->reveal());

        $returnedRevision = $repository->getRevision($revision->getResourceId(), $revision->getVersion());

        $this->assertInstanceOf(Revision::class, $returnedRevision);
        $this->assertEquals($revision, $returnedRevision);
    }

    public function testNotFoundRevision()
    {
        $revision = $this->revisions[0];

        $storage = $this->prophesize(RevisionStorageInterface::class);
        $storage->getRevision(Argument::type('string'), Argument::type('int'))->willThrow(
            RevisionNotFoundException::class
        );

        $repository = $this->getRepositoryService($storage->reveal());

        $this->expectException(RevisionNotFoundException::class);

        $repository->getRevision($revision->getResourceId(), $revision->getVersion());
    }

    public function testCommit()
    {
        $revision = $this->revisions[0];

        $storage = $this->prophesize(RevisionStorageInterface::class);
        $storage->addRevision(Argument::type(Revision::class), Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($revision);

        $repository = $this->getRepositoryService($storage->reveal());

        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('Great author');

        $session = $this->getMockBuilder(common_session_Session::class)->disableOriginalConstructor()->getMock();
        $session->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $sessionProperty = new ReflectionProperty(common_session_SessionManager::class, 'session');
        $sessionProperty->setAccessible(true);
        $sessionProperty->setValue(null, $session);

        $model = $this->getOntologyMock();
        $resource = $model->getResource($revision->getResourceId());

        $triplesManager = $this->createMock(TriplesManagerService::class);
        $triplesManager->method('getPropertyStorageMap')->willReturn([]);
        $triplesManager->method('cloneTriples')->willReturn([]);

        $serviceLocator = $this->getServiceLocatorMock(
            [
                TriplesManagerService::SERVICE_ID => $triplesManager,
                FileSystemService::SERVICE_ID => $this->getFileSystemMock(),
            ]
        );
        $repository->setServiceLocator($serviceLocator);
        $repository->setOption(RepositoryService::OPTION_FILE_SYSTEM, 'testfs');

        $returnedRevision = $repository->commit($resource, $revision->getMessage(), $revision->getVersion());

        $this->assertEquals($revision, $returnedRevision);
    }

    public function testRestore()
    {
        $revision = $this->revisions[0];

        $storage = $this->prophesize(RevisionStorageInterface::class);
        $storage->getData(Argument::type(Revision::class))
            ->shouldBeCalled()
            ->willReturn($this->getTriplesMock());

        $repository = $this->getRepositoryService($storage->reveal());

        $triplesManager = $this->createMock(TriplesManagerService::class);
        $triplesManager->expects($this->once())
            ->method('cloneTriples')
            ->willReturn($this->getTriplesMock());
        $triplesManager->expects($this->once())
            ->method('getPropertyStorageMap')
            ->willReturn([]);

        $rdfInterface = $this->createPartialMock(RdfInterface::class);
        $rdfInterface->expects($this->any())
            ->method('add')
            ->willReturn(true);

        $ontologyMock = $this->createPartialMock(Ontology::class);

        $ontologyMock->expects($this->any())
            ->method('getRdfInterface')
            ->willReturn($rdfInterface);

        $ontologyMock->expects($this->once())
            ->method('getResource')
            ->willReturn($this->getOntologyMock()->getResource('my first subject'));

        $serviceLocator = $this->getServiceLocatorMock(
            [
                Ontology::SERVICE_ID => $ontologyMock,
                TriplesManagerService::SERVICE_ID => $triplesManager
            ]
        );
        $repository->setServiceLocator($serviceLocator);

        $isRevisionRestored = $repository->restore($revision);

        $this->assertEquals(true, $isRevisionRestored);
    }

    public function testGetNextVersion()
    {
        $revisions = [
            new Revision('123', 1, 1582066925, 'Author', 'Message'),
        ];

        $storage = $this->prophesize(RevisionStorageInterface::class);
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
        $triples = $this->getTriplesMock();

        $storage = $this->prophesize(RevisionStorageInterface::class);
        $storage->getRevisionsDataByQuery(Argument::exact('first'))
            ->shouldBeCalled()
            ->willReturn([$triples->get(0)]);

        $repository = $this->getRepositoryService($storage->reveal());

        $ontologyMock = $this->createPartialMock(Ontology::class);
        $ontologyMock->expects($this->once())
            ->method('getResource')
            ->willReturn($this->getOntologyMock()->getResource('my first subject'));

        $serviceLocator = $this->getServiceLocatorMock(
            [
                Ontology::SERVICE_ID => $ontologyMock
            ]
        );

        $repository->setServiceLocator($serviceLocator);

        $found = $repository->searchRevisionResources('first');

        $this->assertCount(1, $found);
        $this->assertInstanceOf(core_kernel_classes_Resource::class, $found[0]);
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

        $storageProperty = new ReflectionProperty(RepositoryService::class, 'storage');
        $storageProperty->setAccessible(true);
        $storageProperty->setValue($repositoryService, $storage);

        return $repositoryService;
    }
}
