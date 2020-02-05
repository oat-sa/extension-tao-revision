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

use core_kernel_classes_Triple;
use Exception;
use oat\generis\Helper\UuidPrimaryKeyTrait;
use oat\taoRevision\model\Revision;

class NewSqlStorage extends RdsStorage
{
    use UuidPrimaryKeyTrait;

    const DATA_RESOURCE_ID = 'id';

    /**
     * @param string $resourceId
     * @param string $version
     * @param string $created
     * @param string $author
     * @param string $message
     * @param core_kernel_classes_Triple[] $data
     * @return Revision
     * @throws Exception
     */
    public function addRevision($resourceId, $version, $created, $author, $message, $data)
    {
        $this->getPersistence()->insert(
            self::REVISION_TABLE_NAME,
            [
                self::REVISION_RESOURCE => (string)$resourceId,
                self::REVISION_VERSION => (string)$version,
                self::REVISION_USER => (string)$author,
                self::REVISION_MESSAGE => (string)$message,
                self::REVISION_CREATED => (string)$created
            ]
        );

        $revision = new Revision($resourceId, $version, $created, $author, $message);

        if (!empty($data)) {
            $this->saveData($revision, $data);
        }
        return $revision;
    }

    /**
     * @param Revision $revision
     * @param array $data
     * @return bool
     * @throws Exception
     */
    protected function saveData(Revision $revision, $data)
    {
        $dataToSave = [];

        foreach ($data as $triple) {
            $dataToSave[] = [
                self::DATA_RESOURCE_ID => (string)$this->getUniquePrimaryKey(),
                self::DATA_RESOURCE => (string)$revision->getResourceId(),
                self::DATA_VERSION => (string)$revision->getVersion(),
                self::DATA_SUBJECT => (string)$triple->subject,
                self::DATA_PREDICATE => (string)$triple->predicate,
                self::DATA_OBJECT => (string)$triple->object,
                self::DATA_LANGUAGE => (string)$triple->lg
            ];
        }

        return $this->getPersistence()->insertMultiple(self::DATA_TABLE_NAME, $dataToSave);
    }

    /**
     * @param string $resourceId
     * @return Revision[]
     */
    public function getAllRevisions($resourceId)
    {

        $queryBuilder = $this->getQueryBuilder()
            ->select('*')
            ->from(self::REVISION_TABLE_NAME)
            ->where(
                sprintf(' `%s` = ? ', self::REVISION_RESOURCE)
            );

        $parameters = [
            $resourceId
        ];
        $variables = $this->getPersistence()
            ->query($queryBuilder->getSQL(), $parameters)
            ->fetchAll();

        return $this->buildRevisionCollection($variables);
    }

    /**
     * @param array $variables
     * @return array
     */
    public function buildRevisionCollection(array $variables): array
    {
        $revisions = [];
        foreach ($variables as $variable) {
            $revisions[] = new Revision(
                $variable[self::REVISION_RESOURCE],
                $variable[self::REVISION_VERSION],
                $variable[self::REVISION_CREATED],
                $variable[self::REVISION_USER],
                $variable[self::REVISION_MESSAGE]
            );
        }
        return $revisions;
    }
}
