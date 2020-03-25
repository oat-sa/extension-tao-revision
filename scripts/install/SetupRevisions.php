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

use common_Exception;
use Doctrine\DBAL\Schema\SchemaException;
use oat\generis\persistence\PersistenceManager;
use oat\oatbox\extension\InstallAction;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\exception\InvalidService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\taoRevision\model\RepositoryService;
use oat\generis\persistence\sql\SchemaProviderInterface;

/**
 * Setups and configure revisions extension for work
 * @package oat\taoRevision\scripts\install
 */
class SetupRevisions extends InstallAction
{
    /**
     * @param $params
     *
     * @throws common_Exception
     * @throws InvalidServiceManagerException
     */
    public function __invoke($params)
    {
        $fss = $this->getServiceManager()->get(FileSystemService::SERVICE_ID);
        $fss->createFileSystem(RepositoryService::FILE_SYSTEM_NAME, 'tao/revisions');
        $this->getServiceManager()->register(FileSystemService::SERVICE_ID, $fss);

        $this->createTables();
    }

    /**
     * @throws InvalidServiceManagerException
     * @throws InvalidService
     */
    private function createTables()
    {
        $repositoryService = $this->getServiceManager()->get(RepositoryService::SERVICE_ID);
        $storageService = $repositoryService->getSubService(RepositoryService::OPTION_STORAGE);

        if ($storageService instanceof SchemaProviderInterface) {
            $persistenceManager = $this->getServiceLocator()->get(PersistenceManager::SERVICE_ID);
            $schemaCollection = $persistenceManager->getSqlSchemas();
            try {
                $storageService->provideSchema($schemaCollection);
                $persistenceManager->applySchemas($schemaCollection);
            } catch (SchemaException $e) {
                $this->logInfo('Database Schema already up to date.');
            }
        }
    }
}
