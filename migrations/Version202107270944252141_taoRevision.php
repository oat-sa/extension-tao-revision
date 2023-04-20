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
 */

namespace oat\taoRevision\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\taoRevision\model\user\TaoRevisionRoles;
use oat\tao\scripts\update\OntologyUpdater;
use oat\tao\scripts\tools\accessControl\SetRolesAccess;
use oat\tao\scripts\tools\migrations\AbstractMigration;

final class Version202107270944252141_taoRevision extends AbstractMigration
{
    private const CONFIG = [
        SetRolesAccess::CONFIG_RULES => [
            TaoRevisionRoles::REVISION_HISTORY_VIEWER => [
                [
                    'ext' => 'taoRevision',
                    'mod' => 'History',
                    'act' => 'index'
                ],
            ],
            TaoRevisionRoles::REVISION_CREATOR => [
                [
                    'ext' => 'taoRevision',
                    'mod' => 'History',
                    'act' => 'index'
                ],
                [
                    'ext' => 'taoRevision',
                    'mod' => 'History',
                    'act' => 'commitResource'
                ],
            ],
            TaoRevisionRoles::REVISION_MANAGER => [
                [
                    'ext' => 'taoRevision',
                    'mod' => 'History',
                    'act' => 'index'
                ],
                [
                    'ext' => 'taoRevision',
                    'mod' => 'History',
                    'act' => 'commitResource'
                ],
                [
                    'ext' => 'taoRevision',
                    'mod' => 'History',
                    'act' => 'restoreRevision'
                ],
            ],
        ],
    ];

    public function getDescription(): string
    {
        return 'Item content creator role to author existing item';
    }

    public function up(Schema $schema): void
    {
        OntologyUpdater::syncModels();

        $setRolesAccess = $this->propagate(new SetRolesAccess());
        $setRolesAccess(
            [
                '--' . SetRolesAccess::OPTION_CONFIG,
                self::CONFIG,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $setRolesAccess = $this->propagate(new SetRolesAccess());
        $setRolesAccess(
            [
                '--' . SetRolesAccess::OPTION_REVOKE,
                '--' . SetRolesAccess::OPTION_CONFIG, self::CONFIG,
            ]
        );
    }
}
