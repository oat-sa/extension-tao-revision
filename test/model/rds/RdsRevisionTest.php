<?php
/**
 * Created by Antoine on 24/02/15
 * at 13:23
 */

namespace oat\taoRevision\test\model\rds;


use oat\taoRevision\model\rds\RdsRevision;

class RdsRevisionTest extends \PHPUnit_Framework_TestCase {

    private $rdsRevision = null;
    private $id = null;

    public function setUp(){
        $this->id = 'myFunId';
        $resourceId = 123;
        $version = 456;
        $created = time();
        $author = "Great author";
        $message = "My message is really cool";
        $this->rdsRevision = new RdsRevision($this->id, $resourceId, $version, $created, $author, $message);
    }

    public function tearDown(){
        $this->id = null;
        $this->rdsRevision = null;
    }

    public function testConstruct(){
        $this->assertInstanceOf("oat\\taoRevision\\model\\Revision",$this->rdsRevision, "RdsRevision should extends Revision");

    }

    public function testGetId(){

        $this->assertEquals($this->id, $this->rdsRevision->getId(), "The collected id is wrong");

    }

}
 