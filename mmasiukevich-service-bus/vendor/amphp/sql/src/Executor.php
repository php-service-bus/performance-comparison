<?php

namespace Amp\Sql;

use Amp\Promise;

interface Executor extends TransientResource
{
    /**
     * @param string $sql SQL query to execute.
     *
     * @return Promise<CommandResult|ResultSet>
     *
     * @throws FailureException If the operation fails due to unexpected condition.
     * @throws ConnectionException If the connection to the database is lost.
     * @throws QueryError If the operation fails due to an error in the query (such as a syntax error).
     */
    public function query(string $sql): Promise;

    /**
     * @param string $sql SQL query to prepare.
     *
     * @return Promise<Statement>
     *
     * @throws FailureException If the operation fails due to unexpected condition.
     * @throws ConnectionException If the connection to the database is lost.
     * @throws QueryError If the operation fails due to an error in the query (such as a syntax error).
     */
    public function prepare(string $sql): Promise;

    /**
     * @param string $sql SQL query to prepare and execute.
     * @param mixed[] $params Query parameters.
     *
     * @return Promise<CommandResult|ResultSet>
     *
     * @throws FailureException If the operation fails due to unexpected condition.
     * @throws ConnectionException If the connection to the database is lost.
     * @throws QueryError If the operation fails due to an error in the query (such as a syntax error).
     */
    public function execute(string $sql, array $params = []): Promise;

    /**
     * Closes the executor. No further queries may be performed.
     */
    public function close();
}
