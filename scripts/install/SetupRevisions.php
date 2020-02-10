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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */
namespace oat\taoRevision\scripts\install;

use Doctrine\DBAL\Schema\SchemaException;
use oat\generis\persistence\PersistenceManager;
use oat\oatbox\extension\InstallAction;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoRevision\model\RevisionStorage;
use oat\taoRevision\model\SchemaProviderInterface;

class SetupRevisions extends InstallAction {

    public function __invoke($params) {

        $persistenceId = count($params) > 0 ? reset($params) : 'default';

        // create separate file storage
        $fsName = 'revisions';
        $fsm = $this->getServiceManager()->get(FileSystemService::SERVICE_ID);
        $fsm->createFileSystem($fsName, 'tao/revisions');
        $this->getServiceManager()->register(FileSystemService::SERVICE_ID, $fsm);

        $this->createTables();
    }

    private function createTables()
    {

        $storageService = $this->getServiceLocator()->get(RevisionStorage::SERVICE_ID);
        if ($storageService instanceof SchemaProviderInterface) {
            $persistenceId = $storageService->getPersistenceId();
            $persistence = $this->getServiceLocator()
                ->get(PersistenceManager::SERVICE_ID)
                ->getPersistenceById($persistenceId);

            $schemaManager = $persistence->getDriver()->getSchemaManager();
            $schema = $schemaManager->createSchema();
            $fromSchema = clone $schema;

            try {

                $schema = $storageService->getSchema($schema);
                $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
                foreach ($queries as $query) {
                    $persistence->exec($query);
                }
            } catch (SchemaException $e) {
                \common_Logger::i('Database Schema already up to date.');
            }


        }
    }
}
