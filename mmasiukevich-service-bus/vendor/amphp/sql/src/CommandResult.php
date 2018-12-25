<?php

namespace Amp\Sql;

interface CommandResult
{
    /**
     * Returns the number of rows affected by the query.
     *
     * @return int
     */
    public function getAffectedRowCount(): int;
}
