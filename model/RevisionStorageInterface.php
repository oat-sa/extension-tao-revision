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

namespace oat\taoRevision\model;

use core_kernel_classes_Triple as Triple;
use oat\generis\model\OntologyRdfs;

interface RevisionStorageInterface
{
    public const SERVICE_ID = 'taoRevision/storage';

    public const OPTION_PERSISTENCE = 'persistence';

    /**
     * @param Revision $revision
     * @param Triple[] $data
     *
     * @return Revision
     */
    public function addRevision(Revision $revision, array $data);

    /**
     *
     * @param string $resourceId
     * @param int    $version
     *
     * @return Revision
     */
    public function getRevision(string $resourceId, int $version);

    /**
     *
     * @param string $resourceId
     *
     * @return Revision[]
     */
    public function getAllRevisions(string $resourceId);

    /**
     *
     * @param Revision $revision
     * core_kernel_classes_Triple[] $data
     */
    public function getData(Revision $revision);

    /**
     * @param string $query
     * @param array $options
     * @param string $predicate
     *
     * @return array
     */
    public function getResourcesUriByQuery(string $query, array $options = [], string $predicate = OntologyRdfs::RDFS_LABEL);


    /**
     * @param array $variables
     *
     * @return Revision[]
     */
    public function buildRevisionCollection(array $variables);
}
