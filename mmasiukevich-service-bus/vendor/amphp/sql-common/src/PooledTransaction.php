<?php

namespace Amp\Sql\Common;

use Amp\Promise;
use Amp\Sql\ResultSet;
use Amp\Sql\Statement;
use Amp\Sql\Transaction;
use Amp\Sql\TransactionError;
use function Amp\call;

abstract class PooledTransaction implements Transaction
{
    /** @var Transaction|null */
    private $transaction;

    /** @var callable|null */
    private $release;

    /** @var int */
    private $refCount = 1;

    /**
     * Creates a Statement of the appropriate type using the Statement object returned by the Transaction object and
     * the given release callable.
     *
     * @param Statement $statement
     * @param callable  $release
     *
     * @return Statement
     */
    abstract protected function createStatement(Statement $statement, callable $release): Statement;

    /**
     * Creates a ResultSet of the appropriate type using the ResultSet object returned by the Transaction object and
     * the given release callable.
     *
     * @param ResultSet $resultSet
     * @param callable  $release
     *
     * @return ResultSet
     */
    abstract protected function createResultSet(ResultSet $resultSet, callable $release): ResultSet;

    /**
     * @param Transaction $transaction Transaction object created by pooled connection.
     * @param callable    $release Callable to be invoked when the transaction completes or is destroyed.
     */
    public function __construct(Transaction $transaction, callable $release)
    {
        $this->transaction = $transaction;
        $this->release = $release;

        if (!$this->transaction->isActive()) {
            $release();
            $this->transaction = null;
        } else {
            $refCount = &$this->refCount;
            $this->release = static function () use (&$refCount, $release) {
                if (--$refCount === 0) {
                    $release();
                }
            };
        }
    }

    public function __destruct()
    {
        if ($this->transaction && $this->transaction->isActive()) {
            $this->close(); // Invokes $this->release callback.
        }
    }

    public function query(string $sql): Promise
    {
        if (!$this->transaction) {
            throw new TransactionError("The transaction has been committed or rolled back");
        }

        return call(function () use ($sql) {
            $result = yield $this->transaction->query($sql);

            if ($result instanceof ResultSet) {
                ++$this->refCount;
                return $this->createResultSet($result, $this->release);
            }

            return $result;
        });
    }

    public function prepare(string $sql): Promise
    {
        if (!$this->transaction) {
            throw new TransactionError("The transaction has been committed or rolled back");
        }

        return call(function () use ($sql) {
            $statement = yield $this->transaction->prepare($sql);
            ++$this->refCount;
            return $this->createStatement($statement, $this->release);
        });
    }

    public function execute(string $sql, array $params = []): Promise
    {
        if (!$this->transaction) {
            throw new TransactionError("The transaction has been committed or rolled back");
        }

        return call(function () use ($sql, $params) {
            $result = yield $this->transaction->execute($sql, $params);

            if ($result instanceof ResultSet) {
                ++$this->refCount;
                return $this->createResultSet($result, $this->release);
            }

            return $result;
        });
    }

    public function isAlive(): bool
    {
        return $this->transaction && $this->transaction->isAlive();
    }

    public function getLastUsedAt(): int
    {
        if (!$this->transaction) {
            throw new TransactionError("The transaction has been committed or rolled back");
        }

        return $this->transaction->getLastUsedAt();
    }

    public function close()
    {
        if (!$this->transaction) {
            return;
        }

        $promise = $this->transaction->commit();
        $promise->onResolve($this->release);

        $this->transaction = null;
    }

    public function getIsolationLevel(): int
    {
        if (!$this->transaction) {
            throw new TransactionError("The transaction has been committed or rolled back");
        }

        return $this->transaction->getIsolationLevel();
    }

    public function isActive(): bool
    {
        return $this->transaction && $this->transaction->isActive();
    }

    public function commit(): Promise
    {
        if (!$this->transaction) {
            throw new TransactionError("The transaction has been committed or rolled back");
        }

        $promise = $this->transaction->commit();
        $promise->onResolve($this->release);

        $this->transaction = null;

        return $promise;
    }

    public function rollback(): Promise
    {
        if (!$this->transaction) {
            throw new TransactionError("The transaction has been committed or rolled back");
        }

        $promise = $this->transaction->rollback();
        $promise->onResolve($this->release);

        $this->transaction = null;

        return $promise;
    }

    public function createSavepoint(string $identifier): Promise
    {
        if (!$this->transaction) {
            throw new TransactionError("The transaction has been committed or rolled back");
        }

        return $this->transaction->createSavepoint($identifier);
    }

    public function rollbackTo(string $identifier): Promise
    {
        if (!$this->transaction) {
            throw new TransactionError("The transaction has been committed or rolled back");
        }

        return $this->transaction->rollbackTo($identifier);
    }

    public function releaseSavepoint(string $identifier): Promise
    {
        if (!$this->transaction) {
            throw new TransactionError("The transaction has been committed or rolled back");
        }

        return $this->transaction->releaseSavepoint($identifier);
    }
}
