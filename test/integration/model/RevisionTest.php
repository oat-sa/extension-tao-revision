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

namespace oat\taoRevision\test\integration\model\rds;

use oat\taoRevision\model\Revision;
use oat\generis\test\TestCase;

class RevisionTest extends TestCase
{
    private const RESOURCE_ID = '123';
    private const VERSION = 456;
    private const CREATED = 1582066925;
    private const AUTHOR = 'Great author';
    private const MESSAGE = 'My message is really cool';

    /** @var Revision */
    private $revision;

    public function setUp()
    {
        $this->revision = new Revision(self::RESOURCE_ID, self::VERSION, self::CREATED, self::AUTHOR, self::MESSAGE);
    }

    public function tearDown()
    {
        $this->revision = null;
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(Revision::class, $this->revision);
    }

    public function testGetters()
    {
        $this->assertEquals(self::RESOURCE_ID, $this->revision->getResourceId());
        $this->assertEquals(self::VERSION, $this->revision->getVersion());
        $this->assertEquals(self::CREATED, $this->revision->getDateCreated());
        $this->assertEquals(self::AUTHOR, $this->revision->getAuthorId());
        $this->assertEquals(self::MESSAGE, $this->revision->getMessage());
    }
}
