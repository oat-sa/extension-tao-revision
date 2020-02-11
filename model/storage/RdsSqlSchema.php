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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoRevision\model\storage;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use oat\oatbox\service\ConfigurableService;

class RdsSqlSchema extends ConfigurableService
{
    public function createRevisionTable(Table $revisionTable)
    {
        $revisionTable->addColumn(RdsStorage::REVISION_RESOURCE, 'string', ['notnull' => false, 'length' => 255]);
        $revisionTable->addColumn(RdsStorage::REVISION_VERSION, 'string', ['notnull' => false, 'length' => 50]);
        $revisionTable->addColumn(RdsStorage::REVISION_USER, 'string', ['notnull' => true, 'length' => 255]);
        $revisionTable->addColumn(RdsStorage::REVISION_CREATED, 'string', ['notnull' => true]);
        $revisionTable->addColumn(RdsStorage::REVISION_MESSAGE, 'string', ['notnull' => true, 'length' => 4000]);
        $revisionTable->setPrimaryKey([RdsStorage::REVISION_RESOURCE, RdsStorage::REVISION_VERSION]);
    }

    public function createRevisionDataTable(Table $dataTable, Table $revisionTable)
    {
        $dataTable->addColumn(RdsStorage::DATA_RESOURCE, 'string', ['notnull' => false, 'length' => 255]);
        $dataTable->addColumn(RdsStorage::DATA_VERSION, 'string', ['notnull' => false, 'length' => 50]);
        $dataTable->addColumn(RdsStorage::DATA_SUBJECT, 'string', ['notnull' => true, 'length' => 255]);
        $dataTable->addColumn(RdsStorage::DATA_PREDICATE, 'string', ['length' => 255]);
        $dataTable->addColumn(RdsStorage::DATA_OBJECT, 'text', ['default' => null, 'notnull' => false]);
        $dataTable->addColumn(RdsStorage::DATA_LANGUAGE, 'string', ['length' => 50]);


        $dataTable->addForeignKeyConstraint(
            $revisionTable,
            array(RdsStorage::REVISION_RESOURCE, RdsStorage::REVISION_VERSION),
            array(RdsStorage::REVISION_RESOURCE, RdsStorage::REVISION_VERSION)
        );
    }

    /**
     * @inheritDoc
     */
    public function getSchema(Schema $schema)
    {
        /** @var Table $revisionTable */
        $revisionTable = $schema->createtable(RdsStorage::REVISION_TABLE_NAME);
        $this->createRevisionTable($revisionTable);
        /** @var Table $dataTable */
        $dataTable = $schema->createtable(RdsStorage::DATA_TABLE_NAME);
        $this->createRevisionDataTable($dataTable, $revisionTable);

        return $schema;
    }
}
