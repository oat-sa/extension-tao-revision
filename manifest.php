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
 *
 */

use oat\taoRevision\controller\History;
use oat\taoRevision\scripts\update\Updater;
use oat\taoRevision\scripts\install\SetupRevisions;

return [
    'name' => 'taoRevision',
    'label' => 'Data Revision Control',
    'description' => 'Allows saving the intermediate state of objects and restoring them',
    'license' => 'GPL-2.0',
    'author' => 'Open Assessment Technologies SA',
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoRevisionManager',
    'acl' => [
        ['grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoRevisionManager', ['ext' => 'taoRevision']],
        ['grant', 'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemAuthor', ['controller' => History::class]],
        ['grant', 'http://www.tao.lu/Ontologies/TAOItem.rdf#TestAuthor', ['controller' => History::class]],
    ],
    'install' => [
        'rdf' => [
            __DIR__ . '/model/ontology/tao-revision.rdf',
        ],
        'php' => [
            SetupRevisions::class,
        ],
    ],
    'update' => Updater::class,
    'routes' => [
        '/taoRevision' => 'oat\\taoRevision\\controller',
    ],
    'constants' => [
        # views directory
        'DIR_VIEWS' => __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . 'taoRevision/',
    ],
    'extra' => [
        'structures' => __DIR__ . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    ],
];
