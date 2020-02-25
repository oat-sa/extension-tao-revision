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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoRevision\test\integration\model;

use common_Object;
use core_kernel_classes_Triple as Triple;
use core_kernel_classes_ContainerCollection as TriplesCollection;

trait TriplesMockTrait
{
    private function getTriplesMock()
    {
        $tripleOne = new Triple();
        $tripleOne->modelid = 1;
        $tripleOne->subject = 'my first subject';
        $tripleOne->predicate = 'my first predicate';
        $tripleOne->object = 'my first object';
        $tripleOne->lg = 'en-en';

        $tripleTwo = new Triple();
        $tripleTwo->modelid = 1;
        $tripleTwo->subject = 'my second subject';
        $tripleTwo->predicate = 'my second predicate';
        $tripleTwo->object = 'my second object';
        $tripleTwo->lg = 'fr-fr';

        $collection = new TriplesCollection(new common_Object());
        $collection->add($tripleOne);
        $collection->add($tripleTwo);

        return $collection;
    }
}
