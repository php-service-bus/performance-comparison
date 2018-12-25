<?php

namespace Amp\Sql\Common\Test;

use Amp\Delayed;
use Amp\Loop;
use Amp\Promise;
use Amp\Sql\Common\ConnectionPool;
use Amp\Sql\ConnectionConfig;
use Amp\Sql\Connector;
use Amp\Sql\Link;
use Amp\Success;
use PHPUnit\Framework\TestCase;

class ConnectionPoolTest extends TestCase
{
    /**
     * @expectedException \Error
     * @expectedExceptionMessage Pool must contain at least one connection
     */
    public function testInvalidMaxConnections()
    {
        $mock = $this->getMockBuilder(ConnectionPool::class)
            ->setConstructorArgs([$this->createMock(ConnectionConfig::class), 0])
            ->getMock();
    }

    public function testIdleConnectionsRemovedAfterTimeout()
    {
        Loop::run(function () {
            $now = \time();

            $connector = $this->createMock(Connector::class);
            $connector->method('connect')
                ->willReturnCallback(function () use ($now): Promise {
                    $link = $this->createMock(Link::class);
                    $link->method('getLastUsedAt')
                        ->willReturn($now);

                    $link->method('isAlive')
                        ->willReturn(true);

                    $link->method('query')
                        ->willReturnCallback(function () {
                            return new Delayed(100);
                        });

                    return new Success($link);
                });

            /** @var ConnectionPool $pool */
            $pool = $this->getMockBuilder(ConnectionPool::class)
                ->setConstructorArgs([$this->createMock(ConnectionConfig::class), 100, 2, $connector])
                ->getMockForAbstractClass();

            $count = 3;

            $promises = [];
            for ($i = 0; $i < $count; ++$i) {
                $promises[] = $pool->query("SELECT $i");
            }

            $results = yield $promises;

            $this->assertSame($count, $pool->getConnectionCount());

            yield new Delayed(1000);

            $this->assertSame($count, $pool->getConnectionCount());

            yield new Delayed(1000);

            $this->assertSame(0, $pool->getConnectionCount());
        });
    }
}
