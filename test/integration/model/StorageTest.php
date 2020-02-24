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

namespace oat\taoRevision\test\integration\model;

use common_ext_Namespace;
use common_Object;
use core_kernel_classes_Triple as Triple;
use core_kernel_classes_ContainerCollection as TriplesCollection;
use oat\generis\persistence\PersistenceManager;
use oat\generis\test\TestCase;
use oat\taoRevision\model\RevisionNotFoundException;
use oat\taoRevision\model\RevisionStorageInterface;
use oat\taoRevision\model\storage\RdsSqlSchema;
use oat\taoRevision\model\storage\RdsStorage;
use oat\taoRevision\model\Revision;

class StorageTest extends TestCase
{
    /** @var TestRdsStorage */
    private $storage;

    public function setUp()
    {
        $persistenceKey = 'persistence';
        $persistenceManager = $this->getSqlMock($persistenceKey);

        $serviceLocator = $this->getServiceLocatorMock([PersistenceManager::SERVICE_ID => $persistenceManager]);

        $rds = $persistenceManager->getPersistenceById($persistenceKey);
        $schema = $rds->getSchemaManager()->createSchema();

        $rdsSchema = new RdsSqlSchema();
        $rdsSchema->setServiceLocator($serviceLocator);
        $schema = $rdsSchema->getSchema($schema);

        $queries = $rds->getPlatform()->schemaToSql($schema);
        foreach ($queries as $query) {
            $rds->query($query);
        }

        $this->storage = new TestRdsStorage([RevisionStorageInterface::OPTION_PERSISTENCE => $persistenceKey]);
        $this->storage->setServiceLocator($serviceLocator);
    }

    public function tearDown()
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
            ->getPersistenceById('persistence');

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

    public function testGetRevisionsDataByQuery()
    {
        $triples = $this->getTriplesMock();
        $revision = new Revision('123', 456, time(), 'author', 'message');

        $this->storage->addRevision($revision, $triples->toArray());

        $data = $this->storage->getRevisionsDataByQuery('first', 'my first predicate');

        $this->assertEquals([$triples->get(0)], $data);
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

    /**
     * @return TriplesCollection
     */
    private function getTriplesMock()
    {
        $tripleOne = new Triple();
        $tripleOne->modelid = 1;
        $tripleOne->subject = 'my first subject';
        $tripleOne->predicate = 'my first predicate';
        $tripleOne->object = 'my first object';
        $tripleOne->lg = 'en-en';

        $tripleTwo = new Triple();
        $tripleTwo->modelid = 1;
        $tripleTwo->subject = 'my second subject';
        $tripleTwo->predicate = 'my second predicate';
        $tripleTwo->object = 'my second object';
        $tripleTwo->lg = 'fr-fr';

        $collection = new TriplesCollection(new common_Object());
        $collection->add($tripleOne);
        $collection->add($tripleTwo);

        return $collection;
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
