<?php

namespace Amp\Sql;

abstract class ConnectionConfig
{
    const KEY_MAP = [
        'username' => 'user',
        'pass' => 'password',
        'database' => 'db',
        'dbname' => 'db',
    ];

    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var string|null */
    private $user;

    /** @var string|null */
    private $password;

    /** @var string|null */
    private $database;

    /**
     * Parses a connection string into an array of keys and values given.
     *
     * @param string $connectionString
     * @param string[] $keymap Map of alternative key names to canonical key names.
     *
     * @return array
     */
    protected static function parseConnectionString(string $connectionString, array $keymap = self::KEY_MAP): array
    {
        $values = [];

        $params = \explode(";", $connectionString);

        if (\count($params) === 1) { // Attempt to explode on a space if no ';' are found.
            $params = \explode(" ", $connectionString);
        }

        foreach ($params as $param) {
            list($key, $value) = \array_map("trim", \explode("=", $param, 2) + [1 => null]);

            if (isset($keymap[$key])) {
                $key = $keymap[$key];
            }

            $values[$key] = $value;
        }

        return $values;
    }

    public function __construct(string $host, int $port, string $user = null, string $password = null, string $database = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
    }

    final public function getHost(): string
    {
        return $this->host;
    }

    final public function withHost(string $host): self
    {
        $new = clone $this;
        $new->host = $host;
        return $new;
    }

    final public function getPort(): int
    {
        return $this->port;
    }

    final public function withPort(int $port): self
    {
        $new = clone $this;
        $new->port = $port;
        return $new;
    }

    /**
     * @return string|null
     */
    final public function getUser() /* : ?string */
    {
        return $this->user;
    }

    final public function withUser(string $user = null): self
    {
        $new = clone $this;
        $new->user = $user;
        return $new;
    }

    /**
     * @return string|null
     */
    final public function getPassword() /* : ?string */
    {
        return $this->password;
    }

    final public function withPassword(string $password = null): self
    {
        $new = clone $this;
        $new->password = $password;
        return $new;
    }

    /**
     * @return string|null
     */
    final public function getDatabase() /* : ?string */
    {
        return $this->database;
    }

    final public function withDatabase(string $database = null): self
    {
        $new = clone $this;
        $new->database = $database;
        return $new;
    }
}
