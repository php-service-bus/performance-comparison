<?php

namespace Amp\Sql\Test;

use Amp\PHPUnit\TestCase;
use Amp\Sql\QueryError;

class QueryErrorTest extends TestCase
{
    /**
     * @test
     */
    public function testItPassesQueryAlong()
    {
        $error = new QueryError('error', 'SELECT * FROM foo');

        $this->assertSame('SELECT * FROM foo', $error->getQuery());
        $this->assertStringStartsWith("Amp\Sql\QueryError: error\nCurrent query was SELECT * FROM foo", (string) $error);
    }
}
