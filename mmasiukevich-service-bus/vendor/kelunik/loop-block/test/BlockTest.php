<?php

namespace Kelunik\LoopBlock;

use Amp\Loop;
use Amp\PHPUnit\TestCase;

class BlockTest extends TestCase {
    public function setUp() {
        Loop::set(new Loop\EvDriver());
    }

    /**
     * @param int $threshold Measure threshold.
     * @param int $interval Check interval.
     * @param int $expectedCallCount Minimum callback calls.
     *
     * @test
     * @dataProvider provideArgs
     */
    public function callsCallbackOnBlock($threshold, $interval, $expectedCallCount) {
        $callCount = 0;

        $detector = new BlockDetector(function () use (&$callCount) {
            $callCount++;
        }, $threshold, $interval);

        Loop::run(function () use ($detector) {
            $detector->start();

            Loop::repeat(0, function () {
                usleep(100 * 1000);
            });

            Loop::delay(300, function () {
                Loop::stop();
            });
        });

        $this->assertGreaterThanOrEqual($expectedCallCount, $callCount);
    }

    public function provideArgs() {
        return [
            [10, 0, 2],
            [90, 0, 2],
            [110, 0, 0],
            [300, 0, 0],
        ];
    }
}
