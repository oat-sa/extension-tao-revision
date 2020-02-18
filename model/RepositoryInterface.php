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

interface RepositoryInterface
{
    public const SERVICE_ID = 'taoRevision/repository';

    /**
     * Returns an array of Revision objects for a given resource
     *
     * @param string $resourceId
     * @return Revision[]
     */
    public function getAllRevisions(string $resourceId): array;

    /**
     * Returns revision, a specific version of changes
     *
     * @param string $resourceId
     * @param int $version
     * @throws RevisionNotFoundException
     * @return Revision
     */
    public function getRevision(string $resourceId, int $version): Revision;

    /**
     * Stores changes in the history
     *
     * @param string      $resourceId
     * @param string      $message
     * @param int|null    $version
     * @param string|null $userId
     *
     * @return Revision
     */
    public function commit(string $resourceId, string $message, int $version = null, string $userId = null): Revision;
    
    /**
     * Restore a previous version
     *
     * @param Revision $revision
     * @return bool
     */
    public function restore(Revision $revision): bool;
}
