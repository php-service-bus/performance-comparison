<?php

namespace Amp\Sql\Common;

use Amp\Loop;
use Amp\Promise;
use Amp\Sql\Pool;
use Amp\Sql\ResultSet;
use Amp\Sql\Statement;
use function Amp\call;

abstract class StatementPool implements Statement
{
    /** @var \Amp\Sql\Pool */
    private $pool;

    /** @var \SplQueue */
    private $statements;

    /** @var string */
    private $sql;

    /** @var int */
    private $lastUsedAt;

    /** @var string */
    private $timeoutWatcher;

    /** @var callable */
    private $prepare;

    /**
     * Performs any necessary actions to the statement to prepare it for execution, returning a promise for the same or
     * a new Statement object if necessary.
     *
     * @param Statement $statement
     *
     * @return Promise<Statement>
     */
    abstract protected function prepare(Statement $statement): Promise;

    /**
     * @param ResultSet $resultSet
     * @param callable  $release
     *
     * @return ResultSet
     */
    abstract protected function createResultSet(ResultSet $resultSet, callable $release): ResultSet;

    /**
     * @param Pool $pool Pool used to re-create the statement if the original closes.
     * @param Statement $statement Original prepared statement returned from the Link.
     * @param callable $prepare Callable that returns a new prepared statement.
     */
    public function __construct(Pool $pool, Statement $statement, callable $prepare)
    {
        $this->lastUsedAt = \time();
        $this->statements = $statements = new \SplQueue;
        $this->pool = $pool;
        $this->prepare = $prepare;
        $this->sql = $statement->getQuery();

        $this->statements->push($statement);

        $this->timeoutWatcher = Loop::repeat(1000, static function () use ($pool, $statements) {
            $now = \time();
            $idleTimeout = ((int) ($pool->getIdleTimeout() / 10)) ?: 1;

            while (!$statements->isEmpty()) {
                $statement = $statements->bottom();
                \assert($statement instanceof Statement);

                if ($statement->getLastUsedAt() + $idleTimeout > $now) {
                    return;
                }

                $statements->shift();
            }
        });

        Loop::unreference($this->timeoutWatcher);
    }

    public function __destruct()
    {
        Loop::cancel($this->timeoutWatcher);
    }

    /**
     * {@inheritdoc}
     *
     * Unlike regular statements, as long as the pool is open this statement will not die.
     */
    public function execute(array $params = []): Promise
    {
        $this->lastUsedAt = \time();

        return call(function () use ($params) {
            $statement = yield from $this->pop();
            \assert($statement instanceof Statement);

            try {
                $statement = yield $this->prepare($statement);
                \assert($statement instanceof Statement);
                $result = yield $statement->execute($params);
            } catch (\Throwable $exception) {
                $this->push($statement);
                throw $exception;
            }

            if ($result instanceof ResultSet) {
                $result = $this->createResultSet($result, function () use ($statement) {
                    $this->push($statement);
                });
            } else {
                $this->push($statement);
            }

            return $result;
        });
    }

    /**
     * Only retains statements if less than 10% of the pool is consumed by this statement and the pool has
     * available connections.
     *
     * @param Statement $statement
     */
    protected function push(Statement $statement)
    {
        $maxConnections = $this->pool->getConnectionLimit();

        if ($this->statements->count() > ($maxConnections / 10)) {
            return;
        }

        if ($maxConnections === $this->pool->getConnectionCount() && $this->pool->getIdleConnectionCount() === 0) {
            return;
        }

        $this->statements->push($statement);
    }

    /**
     * Coroutine returning a Statement object from the pool or creating a new Statement.
     *
     * @return \Generator
     */
    protected function pop(): \Generator
    {
        while (!$this->statements->isEmpty()) {
            $statement = $this->statements->shift();
            \assert($statement instanceof Statement);

            if ($statement->isAlive()) {
                return $statement;
            }
        }

        $statement = yield ($this->prepare)($this->sql);
        \assert($statement instanceof Statement);
        return $statement;
    }

    /** {@inheritdoc} */
    public function isAlive(): bool
    {
        return $this->pool->isAlive();
    }

    /** {@inheritdoc} */
    public function getQuery(): string
    {
        return $this->sql;
    }

    /** {@inheritdoc} */
    public function getLastUsedAt(): int
    {
        return $this->lastUsedAt;
    }
}
