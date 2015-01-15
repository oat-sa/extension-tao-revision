<?php
use oat\taoRevision\model\rds\Storage;
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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

$persistence = common_persistence_Manager::getPersistence('default');

$schemaManager = $persistence->getDriver()->getSchemaManager();
$schema = $schemaManager->createSchema();
$fromSchema = clone $schema;

try {
    
    $revisionTable = $schema->createtable(Storage::REVISION_TABLE_NAME);
    $revisionTable->addOption('engine', 'MyISAM');
    
    $revisionTable->addColumn(Storage::REVISION_ID, "integer",array("notnull" => true,"autoincrement" => true));
    $revisionTable->addColumn(Storage::REVISION_RESOURCE, "string", array("notnull" => false, "length" => 255));
    $revisionTable->addColumn(Storage::REVISION_VERSION, "string", array("notnull" => false, "length" => 50));
    $revisionTable->addColumn(Storage::REVISION_USER, "string", array("notnull" => true, "length" => 255));
    $revisionTable->addColumn(Storage::REVISION_CREATED, "string", array("notnull" => true));
    $revisionTable->addColumn(Storage::REVISION_MESSAGE, "string", array("notnull" => true, "length" => 4000));
    $revisionTable->setPrimaryKey(array(Storage::REVISION_ID));
    
    $dataTable = $schema->createtable(Storage::DATA_TABLE_NAME);
    $dataTable->addOption('engine', 'MyISAM');
    $dataTable->addColumn(Storage::DATA_REVISION, "integer", array("notnull" => true));
    $dataTable->addColumn(Storage::DATA_SUBJECT, "string", array("notnull" => true, "length" => 255));
    $dataTable->addColumn(Storage::DATA_PREDICATE, "string", array("length" => 255));
    // not compatible with oracle
    $dataTable->addColumn(Storage::DATA_OBJECT, "text", array("default" => null,"notnull" => false));
    $dataTable->addColumn(Storage::DATA_LANGUAGE, "string", array("length" => 50));
    
    $dataTable->addForeignKeyConstraint(
        $revisionTable,
        array(Storage::DATA_REVISION),
        array(Storage::REVISION_ID)
    );

} catch(SchemaException $e) {
    common_Logger::i('Database Schema already up to date.');
}

$queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
foreach ($queries as $query) {
    $persistence->exec($query);
}