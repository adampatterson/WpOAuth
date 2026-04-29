<?php

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WpOAuthTest extends TestCase
{

    #[Test]
    public function testNumberFormatWhole()
    {
        $this->assertEquals(1234, 1234);
    }
}