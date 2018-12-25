<?php

namespace Amp\Sql;

use Amp\Iterator;

interface ResultSet extends Iterator
{
    /**
     * {@inheritdoc}
     *
     * @return array Map of row names to values.
     */
    public function getCurrent(): array;
}
