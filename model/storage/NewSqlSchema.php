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

class NewSqlSchema extends ConfigurableService
{
    public function createRevisionTable(Table $revisionTable)
    {
        $revisionTable->addColumn(NewSqlStorage::REVISION_RESOURCE, 'string', ['notnull' => false, 'length' => 255]);
        $revisionTable->addColumn(NewSqlStorage::REVISION_VERSION, 'string', ['notnull' => false, 'length' => 50]);
        $revisionTable->addColumn(NewSqlStorage::REVISION_USER, 'string', ['notnull' => true, 'length' => 255]);
        $revisionTable->addColumn(NewSqlStorage::REVISION_CREATED, 'string', ['notnull' => true]);
        $revisionTable->addColumn(NewSqlStorage::REVISION_MESSAGE, 'string', ['notnull' => true, 'length' => 4000]);
        $revisionTable->setPrimaryKey([NewSqlStorage::REVISION_RESOURCE, NewSqlStorage::REVISION_VERSION]);
    }

    public function createRevisionDataTable(Table $dataTable, Table $revisionTable)
    {
        $dataTable->addColumn(NewSqlStorage::DATA_RESOURCE_ID, 'string', ['notnull' => false, 'length' => 50]);
        $dataTable->addColumn(NewSqlStorage::DATA_RESOURCE, 'string', ['notnull' => false, 'length' => 255]);
        $dataTable->addColumn(NewSqlStorage::DATA_VERSION, 'string', ['notnull' => false, 'length' => 50]);
        $dataTable->addColumn(NewSqlStorage::DATA_SUBJECT, 'string', ['notnull' => true, 'length' => 255]);
        $dataTable->addColumn(NewSqlStorage::DATA_PREDICATE, 'string', ['length' => 255]);
        $dataTable->addColumn(NewSqlStorage::DATA_OBJECT, 'text', ['default' => null, 'notnull' => false]);
        $dataTable->addColumn(NewSqlStorage::DATA_LANGUAGE, 'string', ['length' => 50]);

        $dataTable->setPrimaryKey([NewSqlStorage::DATA_RESOURCE_ID]);

        $dataTable->addForeignKeyConstraint(
            $revisionTable,
            array(NewSqlStorage::REVISION_RESOURCE, NewSqlStorage::REVISION_VERSION),
            array(NewSqlStorage::REVISION_RESOURCE, NewSqlStorage::REVISION_VERSION)
        );
    }

    /**
     * @inheritDoc
     */
    public function getSchema(Schema $schema)
    {
        /** @var Table $revisionTable */
        $revisionTable = $schema->createtable(NewSqlStorage::REVISION_TABLE_NAME);
        $this->createRevisionTable($revisionTable);
        /** @var Table $dataTable */
        $dataTable = $schema->createtable(NewSqlStorage::DATA_TABLE_NAME);
        $this->createRevisionDataTable($dataTable, $revisionTable);

        return $schema;
    }
}
