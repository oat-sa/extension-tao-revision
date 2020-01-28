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
 */

namespace oat\taoRevision\model\storage;

use common_ext_Namespace;
use common_persistence_SqlPersistence;
use core_kernel_classes_Triple;
use oat\generis\model\kernel\persistence\smoothsql\search\driver\TaoSearchDriver;
use oat\generis\model\OntologyRdfs;
use oat\taoRevision\model\RevisionNotFound;
use oat\taoRevision\model\Revision;
use oat\oatbox\service\ConfigurableService;
use oat\taoRevision\model\RevisionStorage;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Storage class for the revision data
 *
 * @author Joel Bout <joel@taotesting.com>
 */
class RdsStorage extends AbstractStorage
{
}
