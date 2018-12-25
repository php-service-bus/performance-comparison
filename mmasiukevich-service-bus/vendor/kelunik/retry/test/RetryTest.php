<?php

namespace Kelunik\Retry\Test;

use PHPUnit\Framework\TestCase;
use function Amp\Promise\wait;
use function Kelunik\Retry\retry;

class RetryTest extends TestCase {
    public function testBasic() {
        $this->assertSame(42, wait(retry(1, function () {
            return 42;
        })));
    }

    public function testMaxAttemptsMustBePositive() {
        $this->expectException(\Error::class);

        /* even without wait */
        retry(0, function () {
            return 42;
        });
    }

    public function testThrowableArrayCantBeEmpty() {
        $this->expectException(\Error::class);

        /* even without wait */
        retry(1, function () {
            return 42;
        }, []);
    }

    public function testRetry() {
        $i = 0;

        $this->assertSame(42, wait(retry(2, function () use (&$i) {
            $i++;

            if ($i === 1) {
                throw new \Exception("whoops");
            }

            return 42;
        })));
    }

    public function testRetryWithTooManyAttempts() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("whoops");

        wait(retry(2, function () use (&$i) {
            throw new \Exception("whoops");
        }));
    }

    public function testRetryWithCustomCatch() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("whoops");

        $this->assertSame(42, wait(retry(2, function () use (&$i) {
            $i++;

            if ($i === 1) {
                throw new \Exception("whoops");
            }

            return 42;
        }, \Error::class)));
    }
}
