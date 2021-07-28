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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA; *
 */

declare(strict_types=1);

namespace oat\taoRevision\model\user;

interface TaoRevisionRoles
{
    public const ASSET_REVISION_HISTORY_VIEWER = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AssetRevisionHistoryViewer';
    public const ASSET_REVISION_CREATOR = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AssetRevisionCreator';
    public const ASSET_REVISION_MANAGER = 'http://www.tao.lu/Ontologies/TAOMedia.rdf#AssetRevisionManager';
}
