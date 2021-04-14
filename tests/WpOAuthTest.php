<?php

use WpOAuth\WpOAuth;
use PHPUnit\Framework\TestCase;

class WpOAuthTest extends TestCase
{

    public function testNumberFormatWhole()
    {
        $this->assertEquals(1234, 1234);
    }
}