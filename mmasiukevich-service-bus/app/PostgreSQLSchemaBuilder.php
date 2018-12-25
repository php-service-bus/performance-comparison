<?php

declare(strict_types = 1);

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;

/**
 * Generate PostgreSQL schema for service-bus components
 */
final class PostgreSQLSchemaBuilder
{
    /**
     * @var StorageAdapter
     */
    private $adapter;

    /**
     * @var string
     */
    private $serviceBusDirectory;

    /**
     * @param StorageAdapter $adapter
     */
    public function __construct(StorageAdapter $adapter)
    {
        $this->adapter             = $adapter;
        $this->serviceBusDirectory = __DIR__ . '/../vendor/mmasiukevich/service-bus';
    }

    /**
     * Create all schemas
     *
     * @return Promise
     */
    public function build(): Promise
    {
        return call(
            function(): \Generator
            {
                yield $this->enableUid();
                yield $this->eventSourcing();
                yield $this->sagas();
                yield $this->indexer();
                yield $this->scheduler();
            }
        );
    }

    /**
     * Enable uuid extension
     *
     * @return Promise
     */
    public function enableUid(): Promise
    {
        return $this->adapter->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
    }

    /**
     * Create Event Sourcing schema
     *
     * @return Promise
     */
    public function eventSourcing(): Promise
    {
        return call(
            function(): \Generator
            {
                yield $this->importFixture($this->serviceBusDirectory . '/src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream.sql');
                yield $this->importFixture($this->serviceBusDirectory . '/src/EventSourcing/EventStreamStore/Sql/schema/event_store_stream_events.sql');
                yield $this->importFixture($this->serviceBusDirectory . '/src/EventSourcing/EventStreamStore/Sql/schema/event_store_snapshots.sql');
                yield $this->importFixture($this->serviceBusDirectory . '/src/EventSourcing/EventStreamStore/Sql/schema/indexes.sql', true);
            }
        );
    }

    /**
     * Create Sagas schema
     *
     * @return Promise
     */
    public function sagas(): Promise
    {
        return call(
            function(): \Generator
            {
                yield $this->importFixture($this->serviceBusDirectory . '/src/Sagas/SagaStore/Sql/schema/sagas_store.sql');
                yield $this->importFixture($this->serviceBusDirectory . '/src/Sagas/SagaStore/Sql/schema/indexes.sql', true);
            }
        );
    }

    /**
     * Create Indexer schema
     *
     * @return Promise
     */
    public function indexer(): Promise
    {
        return $this->importFixture($this->serviceBusDirectory . '/src/Index/Storage/Sql/schema/event_sourcing_indexes.sql');
    }

    /**
     * Create Scheduler schema
     *
     * @return Promise
     */
    public function scheduler(): Promise
    {
        return $this->importFixture($this->serviceBusDirectory . '/src/Scheduler/Store/Sql/schema/scheduler_registry.sql');
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
