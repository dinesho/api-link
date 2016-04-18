<?php

use Dinesho\Apilink\Apilink;

class ApilinkTest extends PHPUnit_Framework_TestCase {

    public function testTimestamp()
    {
        $apiLink = new ApiLink("APIKEY","SECRET",1000);
        $this->assertTrue($apiLink->TIMESTAMP>0);
    }

}
