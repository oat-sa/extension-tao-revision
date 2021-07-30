<?php

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
