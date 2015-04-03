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

namespace oat\taoRevision\test\model\rds;
namespace oat\taoRevision\model\rds;


function time()
{
    return RepositoryTest::$now ?: \time();
}


class RepositoryTest extends \PHPUnit_Framework_TestCase {

    public static $now;

    /** @var Repository */
    private $repository = null;
    private $options = array();
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $storage = null;

    public function setUp(){
        $this->options['persistence'] = 123;


        // storage mock
        $this->storage = $this->getMockBuilder('oat\taoRevision\model\rds\Storage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('oat\taoRevision\model\rds\Repository')
            ->setMethods(array('getStorage'))
            ->setConstructorArgs(array($this->options))
            ->getMock();

        $this->repository->expects($this->any())
            ->method('getStorage')
            ->willReturn($this->storage);
    }

    public function tearDown(){
        self::$now = null;
        $this->options = array();
        $this->repository = null;
        $this->storage = null;
    }


    public function testGetRevisions(){

        $returnValue = array(456, 789);
        $revisionId = 123;

        $this->storage->expects($this->once())
            ->method('getAllRevisions')
            ->with($revisionId)
            ->willReturn($returnValue);

        $return = $this->repository->getRevisions($revisionId);

        $this->assertEquals($returnValue, $return);
    }

    public function testGetRevision(){

        $resourceId = "MyId";
        $version = "version";
        $created = time();
        $author = "author";
        $message = "my author";
        $revision = new RdsRevision(111, $resourceId, $version, $created, $author, $message);

        $this->storage->expects($this->once())
            ->method('getRevision')
            ->with($resourceId, $version)
            ->willReturn($revision);

        $return = $this->repository->getRevision($resourceId, $version);

        $this->assertEquals($revision, $return);
    }


    public function testCommit(){

        $resourceId = "MyId";
        $version = "version";
        self::$now = time();
        $author = "author";
        $message = "my message";
        $revision = new RdsRevision(111, $resourceId, $version, self::$now, $author, $message);


        $this->storage->expects($this->once())
            ->method('addRevision')
            ->with($resourceId, $version, self::$now, $author, $message)
            ->willReturn($revision);

        //mock user manager to get custom identifier
        $user = $this->getMockBuilder('oat\oatbox\user\User')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->once())
            ->method('getIdentifier')
            ->willReturn("author");

        $session = $this->getMockBuilder('common_session_Session')
            ->disableOriginalConstructor()
            ->getMock();

        $session->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $ref = new \ReflectionProperty('common_session_SessionManager', 'session');
        $ref->setAccessible(true);
        $ref->setValue(null, $session);

        $return = $this->repository->commit($resourceId, $message, $version);
        $this->assertEquals($revision, $return);

    }

    public function testRestore(){
        $repo = $this->getMockBuilder('oat\taoRevision\model\rds\Repository')
            ->setMethods(array('commit', 'getStorage'))
            ->setConstructorArgs(array($this->options))
            ->getMock();

        $resourceId = "MyId";
        $version = "version";
        $newVersion = "new version";
        self::$now = time();
        $author = "author";
        $message = "my message";
        $newMessage = "my new message";
        $revision = new RdsRevision(111,$resourceId, $version, self::$now, $author, $message);
        $newRevision = new RdsRevision(222, $resourceId, $newVersion, self::$now, $author, $newMessage);
        $data = array();

        $repo->expects($this->once())
            ->method('getStorage')
            ->willReturn($this->storage);

        $repo->expects($this->once())
            ->method('commit')
            ->with($resourceId, $newMessage, $newVersion)
            ->willReturn($newRevision);

        $this->storage->expects($this->once())
            ->method('getData')
            ->with($revision)
            ->willReturn($data);

        $return = $repo->restore($revision, $newVersion, $newMessage);
        $this->assertEquals($newRevision, $return);
    }


}
 