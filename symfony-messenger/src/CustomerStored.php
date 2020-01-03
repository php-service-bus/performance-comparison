<?php

declare(strict_types = 1);

namespace App;


final class CustomerStored
{
    /**
     * @var string
     */
    public $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}