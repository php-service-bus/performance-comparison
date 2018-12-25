<?php

namespace Kelunik\Retry\Test;

use Kelunik\Retry\ConstantBackoff;
use PHPUnit\Framework\TestCase;

class ConstantBackoffTest extends TestCase {
    public function testDoesntAllowNegativeBackoff() {
        $this->expectException(\Error::class);

        new ConstantBackoff(-1);
    }
}
