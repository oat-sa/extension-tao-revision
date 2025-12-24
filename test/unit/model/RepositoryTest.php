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
use oat\generis\model\data\Ontology;
use oat\generis\model\data\RdfInterface;
use oat\generis\test\FileSystemMockTrait;
use oat\generis\test\OntologyMockTrait;
use oat\generis\test\ServiceManagerMockTrait;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoQtiItem\model\qti\event\UpdatedItemEventDispatcher;
use oat\taoQtiItem\model\qti\Item;
use oat\taoQtiItem\model\qti\Service;
use oat\taoRevision\model\Revision;
use oat\taoRevision\model\RepositoryService;
use oat\taoRevision\model\RevisionNotFoundException;
use oat\taoRevision\model\TriplesManagerService;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use oat\taoRevision\model\RevisionStorageInterface;
use oat\oatbox\user\User;
use oat\generis\model\fileReference\FileReferenceSerializer;
use oat\oatbox\filesystem\File;
use core_kernel_classes_Triple as Triple;
use core_kernel_classes_ContainerCollection as TriplesCollection;

class RepositoryTest extends TestCase
{
    use ServiceManagerMockTrait;
    use OntologyMockTrait;
    use FileSystemMockTrait;
    use TriplesMockTrait;

    /** @var Revision[] */
    private array $revisions = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->revisions = [
            new Revision('123', 456, 1582066925, 'Great author', 'My message is really cool'),
            new Revision('123', 789, 1581566925, 'Great author', 'My message is really cool'),
        ];
    }

    public function testGetAllRevisions(): void
    {
        $storage = $this->createMock(RevisionStorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('getAllRevisions')
            ->with($this->isType('string'))
            ->willReturn($this->revisions);

        $repository = $this->getRepositoryService($storage);

        $revisions = $repository->getAllRevisions('123');

        $this->assertEquals($this->revisions, $revisions);
    }

    public function testGetRevision(): void
    {
        $revision = $this->revisions[0];

        $storage = $this->createMock(RevisionStorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('getRevision')
            ->with($this->isType('string'), $this->isType('int'))
            ->willReturn($revision);

        $repository = $this->getRepositoryService($storage);

        $returnedRevision = $repository->getRevision($revision->getResourceId(), $revision->getVersion());

        $this->assertInstanceOf(Revision::class, $returnedRevision);
        $this->assertEquals($revision, $returnedRevision);
    }

    public function testNotFoundRevision(): void
    {
        $revision = $this->revisions[0];

        $storage = $this->createMock(RevisionStorageInterface::class);
        $storage
            ->method('getRevision')
            ->with($this->isType('string'), $this->isType('int'))
            ->willThrowException(new RevisionNotFoundException('ResourceId', 'Version'));

        $repository = $this->getRepositoryService($storage);

        $this->expectException(RevisionNotFoundException::class);

        $repository->getRevision($revision->getResourceId(), $revision->getVersion());
    }

    public function testCommit(): void
    {
        $revision = $this->revisions[0];

        $storage = $this->createMock(RevisionStorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('addRevision')
            ->with($this->isInstanceOf(Revision::class), $this->isType('array'))
            ->willReturn($revision);

        $repository = $this->getRepositoryService($storage);

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

        $serviceLocator = $this->getServiceManagerMock(
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

    public function testRestore(): void
    {
        $revision = $this->revisions[0];

        $storage = $this->createMock(RevisionStorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('getData')
            ->with($this->isInstanceOf(Revision::class))
            ->willReturn($this->getTriplesMock());

        $repository = $this->getRepositoryService($storage);

        $triplesManager = $this->createMock(TriplesManagerService::class);
        $triplesManager->expects($this->once())
            ->method('cloneTriples')
            ->willReturn($this->getTriplesMock());
        $triplesManager->expects($this->once())
            ->method('getPropertyStorageMap')
            ->willReturn([]);

        $rdfInterface = $this->getMockForAbstractClass(RdfInterface::class);
        $rdfInterface->expects($this->any())
            ->method('add')
            ->willReturn(true);

        $ontologyMock = $this->createMock(Ontology::class);

        $ontologyMock->expects($this->any())
            ->method('getRdfInterface')
            ->willReturn($rdfInterface);

        $ontologyMock->expects($this->once())
            ->method('getResource')
            ->willReturn($this->getOntologyMock()->getResource('my first subject'));

        $qtiServiceMock = $this->createMock(Service::class);
        $itemMock = $this->createMock(Item::class);
        $qtiServiceMock->method('getDataItemByRdfItem')->willReturn($itemMock);
        $eventDispatcherMock = $this->createMock(UpdatedItemEventDispatcher::class);
        $eventDispatcherMock->method('dispatch');

        $serviceLocator = $this->getServiceManagerMock(
            [
                Ontology::SERVICE_ID => $ontologyMock,
                TriplesManagerService::SERVICE_ID => $triplesManager,
                UpdatedItemEventDispatcher::class => $eventDispatcherMock,
                Service::class => $qtiServiceMock
            ]
        );
        $repository->setServiceLocator($serviceLocator);

        $isRevisionRestored = $repository->restore($revision);

        $this->assertEquals(true, $isRevisionRestored);
    }

    public function testGetNextVersion(): void
    {
        $revisions = [
            new Revision('123', 1, 1582066925, 'Author', 'Message'),
        ];

        $storage = $this->createMock(RevisionStorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('getAllRevisions')
            ->with($this->isType('string'))
            ->willReturn($revisions);

        $repository = $this->getRepositoryService($storage);

        $getNextVersionMethod = new ReflectionMethod(RepositoryService::class, 'getNextVersion');
        $getNextVersionMethod->setAccessible(true);
        $newVersion = $getNextVersionMethod->invokeArgs($repository, ['123']);

        $this->assertEquals(2, $newVersion);
    }

    public function testSearchRevisionResources(): void
    {
        $storage = $this->createMock(RevisionStorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('getResourcesDataByQuery')
            ->with('first', [])
            ->willReturn(['test']);

        $repository = $this->getRepositoryService($storage);

        $ontologyMock = $this->createMock(Ontology::class);

        $serviceLocator = $this->getServiceManagerMock(
            [
                Ontology::SERVICE_ID => $ontologyMock
            ]
        );

        $repository->setServiceLocator($serviceLocator);

        $found = $repository->searchRevisionResources('first');

        $this->assertCount(1, $found);
        $this->stringContains($found[0]);
    }

    public function testIsMediaManagerFileWithFileUri(): void
    {
        $storage = $this->createMock(RevisionStorageInterface::class);
        $repository = $this->getRepositoryService($storage);

        $triple = new Triple();
        $triple->subject = 'http://www.tao.lu/test.rdf#i123';
        $triple->predicate = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Link';
        $triple->object = 'file://mediaManager/68dfe0104aafe8.99405914%2Fpassage.xml';

        $isMediaManagerFileMethod = new ReflectionMethod(RepositoryService::class, 'isMediaManagerFile');
        $isMediaManagerFileMethod->setAccessible(true);
        $result = $isMediaManagerFileMethod->invokeArgs($repository, [$triple]);

        $this->assertTrue($result);
    }

    public function testIsMediaManagerFileWithPlainPath(): void
    {
        $storage = $this->createMock(RevisionStorageInterface::class);
        $repository = $this->getRepositoryService($storage);

        $triple = new Triple();
        $triple->subject = 'http://www.tao.lu/test.rdf#i123';
        $triple->predicate = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Link';
        $triple->object = '68dfe0104aafe8.99405914/passage.xml';

        $isMediaManagerFileMethod = new ReflectionMethod(RepositoryService::class, 'isMediaManagerFile');
        $isMediaManagerFileMethod->setAccessible(true);
        $result = $isMediaManagerFileMethod->invokeArgs($repository, [$triple]);

        $this->assertFalse($result);
    }

    public function testRestoreDeserializesFileUris(): void
    {
        $revision = $this->revisions[0];

        $tripleWithFileUri = new Triple();
        $tripleWithFileUri->subject = 'http://www.tao.lu/test.rdf#i123';
        $tripleWithFileUri->predicate = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Link';
        $tripleWithFileUri->object = 'file://mediaManager/68dfe0104aafe8.99405914%2Fpassage.xml';

        $tripleWithPlainPath = new Triple();
        $tripleWithPlainPath->subject = 'http://www.tao.lu/test.rdf#i123';
        $tripleWithPlainPath->predicate = 'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemModel';
        $tripleWithPlainPath->object = 'http://www.tao.lu/Ontologies/TAOItem.rdf#QTI';

        $triplesCollection = new TriplesCollection(new \common_Object());
        $triplesCollection->add($tripleWithFileUri);
        $triplesCollection->add($tripleWithPlainPath);

        $storage = $this->createMock(RevisionStorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('getData')
            ->with($this->isInstanceOf(Revision::class))
            ->willReturn($triplesCollection);

        $repository = $this->getRepositoryService($storage);

        $fileMock = $this->createMock(File::class);
        $fileMock->method('getPrefix')->willReturn('68dfe0104aafe8.99405914/passage.xml');

        $fileRefSerializer = $this->createMock(FileReferenceSerializer::class);
        $fileRefSerializer->expects($this->once())
            ->method('unserializeFile')
            ->with('file://mediaManager/68dfe0104aafe8.99405914%2Fpassage.xml')
            ->willReturn($fileMock);

        $triplesManager = $this->createMock(TriplesManagerService::class);
        $triplesManager->expects($this->once())
            ->method('cloneTriples')
            ->willReturn($triplesCollection);
        $triplesManager->expects($this->once())
            ->method('getPropertyStorageMap')
            ->willReturn([]);

        $rdfInterface = $this->getMockForAbstractClass(RdfInterface::class);
        $rdfInterface->expects($this->exactly(2))
            ->method('add')
            ->with($this->callback(function ($triple) {
                if ($triple->predicate === 'http://www.tao.lu/Ontologies/TAOMedia.rdf#Link') {
                    return $triple->object === '68dfe0104aafe8.99405914/passage.xml';
                }
                return true;
            }))
            ->willReturn(true);

        $ontologyMock = $this->createMock(Ontology::class);
        $ontologyMock->expects($this->any())
            ->method('getRdfInterface')
            ->willReturn($rdfInterface);
        $ontologyMock->expects($this->once())
            ->method('getResource')
            ->willReturn($this->getOntologyMock()->getResource('my first subject'));

        $qtiServiceMock = $this->createMock(Service::class);
        $itemMock = $this->createMock(Item::class);
        $qtiServiceMock->method('getDataItemByRdfItem')->willReturn($itemMock);
        $eventDispatcherMock = $this->createMock(UpdatedItemEventDispatcher::class);
        $eventDispatcherMock->method('dispatch');

        $serviceLocator = $this->getServiceManagerMock(
            [
                Ontology::SERVICE_ID => $ontologyMock,
                TriplesManagerService::SERVICE_ID => $triplesManager,
                FileReferenceSerializer::SERVICE_ID => $fileRefSerializer,
                UpdatedItemEventDispatcher::class => $eventDispatcherMock,
                Service::class => $qtiServiceMock
            ]
        );
        $repository->setServiceLocator($serviceLocator);

        $isRevisionRestored = $repository->restore($revision);

        $this->assertTrue($isRevisionRestored);
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
