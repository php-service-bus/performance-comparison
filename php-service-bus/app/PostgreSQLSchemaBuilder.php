<?php

declare(strict_types = 1);

use ServiceBus\Storage\Common\DatabaseAdapter;
use function Amp\call;
use Amp\Promise;

/**
 * Generate PostgreSQL schema for service-bus components
 */
final class PostgreSQLSchemaBuilder
{
    /**
     * @var DatabaseAdapter
     */
    private $adapter;

    /**
     * @param DatabaseAdapter $adapter
     */
    public function __construct(DatabaseAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Import fixtures from sql
     *
     * @param string $fileName
     * @param bool   $multipleQuery
     *
     * @return Promise
     */
    public function importFixture(string $fileName, bool $multipleQuery = false): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(string $fileName, bool $multipleQuery): \Generator
            {
                $content = \file_get_contents($fileName);

                $queries = true === $multipleQuery
                    ? \array_map('trim', \explode(\PHP_EOL, $content))
                    : [$content];

                foreach($queries as $query)
                {
                    if('' !== $query)
                    {
                        yield $this->adapter->execute($query);
                    }
                }
            },
            $fileName, $multipleQuery
        );
    }
}
