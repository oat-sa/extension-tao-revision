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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoRevision\test\unit\model;

use common_ext_Namespace;
use oat\generis\persistence\PersistenceManager;
use oat\generis\test\OntologyMockTrait;
use oat\generis\test\TestCase;
use oat\taoRevision\model\RevisionNotFoundException;
use oat\taoRevision\model\RevisionStorageInterface;
use oat\taoRevision\model\storage\RdsSqlSchema;
use oat\taoRevision\model\storage\RdsStorage;
use oat\taoRevision\model\Revision;

class StorageTest extends TestCase
{
    use TriplesMockTrait;
    use OntologyMockTrait;

    private const PERSISTENCE_KEY = 'mockSql';

    /** @var TestRdsStorage */
    private $storage;

    public function setUp(): void
    {
        $ontologyMock = $this->getOntologyMock();
        $class = $ontologyMock->getClass('http://fakeClass');
        $class->createInstance('Fake Item', '', 'http://fakeUri');

        $persistence = $ontologyMock->getPersistence();

        $persistenceManager = $this->getSqlMock('tmp');
        $rds = $persistenceManager->getPersistenceById('tmp');

        $schema = $rds->getSchemaManager()->createSchema();
        $rdsSchema = new RdsSqlSchema();
        $rdsSchema->setServiceLocator($ontologyMock->getServiceLocator());
        $schema = $rdsSchema->getSchema($schema);

        $queries = $persistence->getPlatform()->schemaToSql($schema);
        foreach ($queries as $query) {
            $persistence->query($query);
        }

        $this->storage = new TestRdsStorage([RevisionStorageInterface::OPTION_PERSISTENCE => self::PERSISTENCE_KEY]);
        $this->storage->setServiceLocator($ontologyMock->getServiceLocator());
    }

    public function tearDown(): void
    {
        $this->storage = null;
    }

    public function testAddRevision()
    {
        $triples = $this->getTriplesMock();
        $revision = new Revision('123', 456, time(), 'author', 'message');

        $persistence = $this->storage
            ->getServiceLocator()
            ->get(PersistenceManager::SERVICE_ID)
            ->getPersistenceById(self::PERSISTENCE_KEY);

        // no any revision in the storage
        $count = $persistence->query('select count(*) as count from revision')->fetch()['count'];
        $countData = $persistence->query('select count(*) as count from revision_data')->fetch()['count'];
        $this->assertEquals(0, $count);
        $this->assertEquals(0, $countData);

        // adding one revision to the storage
        $addedRevision = $this->storage->addRevision($revision, $triples->toArray());
        $count = $persistence->query('select count(*) as count from revision')->fetch()['count'];
        $countData = $persistence->query('select count(*) as count from revision_data')->fetch()['count'];

        $this->assertEquals(1, $count);
        $this->assertEquals(2, $countData);
        $this->assertEquals($revision, $addedRevision);
        $this->assertEquals($triples, $this->storage->getData($addedRevision));
    }

    public function testGetRevision()
    {
        $revision = new Revision('123', 456, time(), 'author', 'message');

        $this->storage->addRevision($revision, []);

        // returned revision is exact that was added
        $returnedRevision = $this->storage->getRevision('123', 456);
        $this->assertEquals($revision, $returnedRevision);

        // incorrect revision version, not found error raised
        $this->expectException(RevisionNotFoundException::class);
        $this->storage->getRevision('123', 789);
    }

    public function testGetAllRevisions()
    {
        $resourceId = '123';

        $revisionOne = new Revision($resourceId, 456, time(), 'author', 'message one');
        $revisionTwo = new Revision($resourceId, 789, time(), 'author', 'message two');

        $revisions = [
            $this->storage->addRevision($revisionOne, []),
            $this->storage->addRevision($revisionTwo, []),
        ];

        $allRevisions = $this->storage->getAllRevisions($resourceId);

        $this->assertEquals($revisions, $allRevisions);
    }

    public function testGetData()
    {
        $triples = $this->getTriplesMock();

        $revision = $this->storage->addRevision(
            new Revision('123', 234, time(), 'author', 'message'),
            $triples->toArray()
        );

        $data = $this->storage->getData($revision);

        $this->assertEquals($triples, $data);
    }

    public function testGetResourcesUriByQuery()
    {
        $triples = $this->getTriplesMock();

        $revision = new Revision('http://fakeUri', 456, time(), 'author', 'message');

        $this->storage->addRevision($revision, $triples->toArray());

        $data = $this->storage->getResourcesUriByQuery('first', [], 'my first predicate');

        $this->assertEquals(['http://fakeUri'], $data);
    }

    public function testGetResourcesDataByQuery()
    {
        $triples = $this->getTriplesMock();

        $revision = new Revision('http://fakeUri', 456, time(), 'author', 'message');

        $this->storage->addRevision($revision, $triples->toArray());

        $data = $this->storage->getResourcesDataByQuery('first', [], 'my first predicate');

        $this->assertCount(1, $data);
        $this->assertEquals(
            [
                'id' => 'http://fakeUri',
                'label' => 'my first object',
            ],
            $data[0]
        );
    }

    public function testBuildRevisionCollection()
    {
        $dataBank = [];

        for ($i = 0; $i < 3; $i++) {
            $resourceId = '123' . $i;
            $version = $i;
            $created = time() + $i;
            $user = 'author ' . $i;
            $message = 'message ' . $i;
            $dataBank['data'][] = [
                RdsStorage::REVISION_RESOURCE => $resourceId,
                RdsStorage::REVISION_VERSION => $version,
                RdsStorage::REVISION_CREATED => $created,
                RdsStorage::REVISION_USER => $user,
                RdsStorage::REVISION_MESSAGE => $message
            ];
            $dataBank['revisions'][] = new Revision($resourceId, $version, $created, $user, $message);
        }

        $this->assertEquals($dataBank['revisions'], $this->storage->buildRevisionCollection($dataBank['data']));
    }
}

class TestRdsStorage extends RdsStorage
{
    protected function getLocalModel()
    {
        return new common_ext_Namespace(1);
    }

    protected function getLike()
    {
        return 'LIKE';
    }
}
