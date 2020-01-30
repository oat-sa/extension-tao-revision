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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoRevision\scripts\install;

use common_ext_action_InstallAction;
use common_persistence_Manager;
use common_persistence_Persistence;
use common_persistence_sql_dbal_SchemaManager;
use Doctrine\DBAL\Schema\SchemaException;
use oat\taoRevision\model\storage\NewSqlStorage;
use oat\taoRevision\model\storage\RdsStorage as Storage;
use oat\oatbox\log\LoggerAwareTrait;

class CreateTables extends common_ext_action_InstallAction
{
    use LoggerAwareTrait;

    public function __invoke($params)
    {

        $persistenceId = count($params) > 0 ? reset($params) : 'default';
        /** @var common_persistence_Persistence $persistence */
        $persistence = $this->getServiceLocator()
            ->get(common_persistence_Manager::SERVICE_KEY)
            ->getPersistenceById($persistenceId);

        /** @var common_persistence_sql_dbal_SchemaManager $schemaManager */
        $schemaManager = $persistence->getDriver()->getSchemaManager();

        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $revisionTable = $schema->createtable(Storage::REVISION_TABLE_NAME);
            $revisionTable->addOption('engine', 'MyISAM');

            $revisionTable->addColumn(Storage::REVISION_RESOURCE, 'string', ['notnull' => false, 'length' => 255]);
            $revisionTable->addColumn(Storage::REVISION_VERSION, 'string', ['notnull' => false, 'length' => 50]);
            $revisionTable->addColumn(Storage::REVISION_USER, 'string', ['notnull' => true, 'length' => 255]);
            $revisionTable->addColumn(Storage::REVISION_CREATED, 'string', ['notnull' => true]);
            $revisionTable->addColumn(Storage::REVISION_MESSAGE, 'string', ['notnull' => true, 'length' => 4000]);
            $revisionTable->setPrimaryKey(array(Storage::REVISION_RESOURCE, Storage::REVISION_VERSION));

            /** @var  $dataTable */
            $dataTable = $schema->createtable(Storage::DATA_TABLE_NAME);
            $dataTable->addOption('engine', 'MyISAM');
            $dataTable->addColumn(Storage::DATA_RESOURCE, 'string', ['notnull' => false, 'length' => 255]);
            $dataTable->addColumn(Storage::DATA_VERSION, 'string', ['notnull' => false, 'length' => 50]);
            $dataTable->addColumn(Storage::DATA_SUBJECT, 'string', ['notnull' => true, 'length' => 255]);
            $dataTable->addColumn(Storage::DATA_PREDICATE, 'string', ['length' => 255]);
            // not compatible with oracle
            $dataTable->addColumn(Storage::DATA_OBJECT, 'text', ['default' => null, 'notnull' => false]);
            $dataTable->addColumn(Storage::DATA_LANGUAGE, 'string', ['length' => 50]);

            if ($persistence->getDriver()->getPlatform()->getName() === 'gcp-spanner') {
                $dataTable->addColumn(
                    NewSqlStorage::DATA_RESOURCE_ID,
                    'string',
                    ['notnull' => false, 'length' => 50]
                );
                $revisionTable->setPrimaryKey([NewSqlStorage::DATA_RESOURCE_ID]);
            }

            $dataTable->addForeignKeyConstraint(
                $revisionTable,
                array(Storage::REVISION_RESOURCE, Storage::REVISION_VERSION),
                array(Storage::REVISION_RESOURCE, Storage::REVISION_VERSION)
            );
        } catch (SchemaException $e) {
            $this->logInfo('Database Schema already up to date.');
        }

        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }
}
