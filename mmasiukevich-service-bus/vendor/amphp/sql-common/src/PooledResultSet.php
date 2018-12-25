<?php

namespace Amp\Sql\Common;

use Amp\Promise;
use Amp\Sql\ResultSet;

class PooledResultSet implements ResultSet
{
    /** @var ResultSet */
    private $result;

    /** @var callable */
    private $release;

    /**
     * @param ResultSet $result ResultSet object created by pooled connection or statement.
     * @param callable  $release Callable to be invoked when the result set is destroyed.
     */
    public function __construct(ResultSet $result, callable $release)
    {
        $this->result = $result;
        $this->release = $release;
    }

    public function __destruct()
    {
        ($this->release)();
    }

    public function advance(): Promise
    {
        return $this->result->advance();
    }

    public function getCurrent(): array
    {
        return $this->result->getCurrent();
    }
}
