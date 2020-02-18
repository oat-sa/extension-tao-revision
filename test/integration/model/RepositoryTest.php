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

namespace oat\taoRevision\test\integration\model;

use common_session_SessionManager;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoRevision\model\Revision;
use oat\taoRevision\model\RepositoryService;
use ReflectionMethod;
use ReflectionProperty;
use Zend\ServiceManager\ServiceLocatorInterface;
use oat\taoRevision\model\RevisionStorageInterface;
use oat\oatbox\user\User;

function time()
{
    return RepositoryTest::$now ?: \time();
}


class RepositoryTest extends TestCase
{
    public static $now;

    private function getRepository($storage)
    {
        $smProphecy = $this->prophesize(ServiceLocatorInterface::class);
        $smProphecy->get('mockStorage')->willReturn($storage);

        $fs = $this->getTempDirectory();

        $fsm = $this->prophesize(FileSystemService::class);
        $smProphecy->get('generis/filesystem')->willReturn($fsm->reveal());

        $method = new ReflectionMethod(Directory::class, 'getFileSystem');
        $method->setAccessible(true);
        $fsm->getFileSystem('mockFS')->willReturn($method->invoke($fs));

        $repository = new RepositoryService([
            RepositoryService::OPTION_STORAGE => 'mockStorage',
            RepositoryService::OPTION_FILE_SYSTEM => 'mockFS'

        ]);

        $repository->setServiceLocator($smProphecy->reveal());

        return $repository;
    }

    public function tearDown()
    {
        self::$now = null;
    }


    public function testGetRevisions()
    {

        $returnValue = array(456, 789);
        $resourceId = 123;

        $storageProphecy = $this->prophesize(RevisionStorageInterface::class);
        $storageProphecy->getAllRevisions($resourceId)->willReturn($returnValue);

        $repository = $this->getRepository($storageProphecy->reveal());

        $return = $repository->getAllRevisions($resourceId);
        $this->assertEquals($returnValue, $return);

        $storageProphecy->getAllRevisions($resourceId)->shouldHaveBeenCalled();
    }

    public function testGetRevision()
    {
        $resourceId = '123';
        $version = 456;
        $created = 1582066925;
        $author = 'Great author';
        $message = 'My message is really cool';

        $revision = new Revision($resourceId, $version, $created, $author, $message);

        $storageProphecy = $this->prophesize(RevisionStorageInterface::class);
        $storageProphecy->getRevision($resourceId, $version)->willReturn($revision);

        $repository = $this->getRepository($storageProphecy->reveal());

        $return = $repository->getRevision($resourceId, $version);

        $this->assertEquals($revision, $return);

        $storageProphecy->getRevision($resourceId, $version)->shouldHaveBeenCalled();
    }

    public function testCommit()
    {
        $resourceId = '123';
        $version = 456;
        $created = 1582066925;
        $author = 'Great author';
        $message = 'My message is really cool';

        $revision = new Revision($resourceId, $version, $created, $author, $message); // ?

        $storageProphecy = $this->prophesize(RevisionStorageInterface::class);
        $storageProphecy->addRevision($revision, [])->willReturn($revision);

        $repository = $this->getRepository($storageProphecy->reveal());

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('Great author');

        $session = $this->getMockBuilder('common_session_Session')
            ->disableOriginalConstructor()
            ->getMock();

        $session->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $ref = new ReflectionProperty(common_session_SessionManager::class, 'session');
        $ref->setAccessible(true);
        $ref->setValue(null, $session);

        $return = $repository->commit($resourceId, $message, $version);

        $this->assertEquals($revision, $return);
    }

    public function testRestore()
    {
        $resourceId = "MyId";
        $version = "version";
        self::$now = time();
        $author = "author";
        $message = "my message";

        $revision = new Revision($resourceId, $version, self::$now, $author, $message);
        $data = array();

        $storageProphecy = $this->prophesize(RevisionStorageInterface::class);
        $storageProphecy->getData($revision)->willReturn($data);

        $repository = $this->getRepository($storageProphecy->reveal());

        $return = $repository->restore($revision);

        $this->assertEquals(true, $return);
    }

    public function testSearchRevisionResources()
    {
        // Initialize the expected values
        $triple = new \core_kernel_classes_Triple();
        $triple->modelid = 1;
        $triple->subject = 'http://mock/Uri';
        $data = array(
            $triple
        );

        $storageProphecy = $this->prophesize(RevisionStorageInterface::class);
        $storageProphecy->getRevisionsDataByQuery('test')->willReturn($data);

        $repository = $this->getRepository($storageProphecy->reveal());

        $return = $repository->searchRevisionResources('test');

        $this->assertInstanceOf(\core_kernel_classes_Resource::class, $return[0]);
    }
}
