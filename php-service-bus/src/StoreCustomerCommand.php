<?php

/**
 * PHP Service Bus comparison
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace App;

use ServiceBus\Common\Messages\Command;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read string $email
 */
final class StoreCustomerCommand implements Command
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $email;

    /**
     * @param string $id
     * @param string $name
     * @param string $email
     */
    public function __construct(string $id, string $name, string $email)
    {
        $this->id    = $id;
        $this->name  = $name;
        $this->email = $email;
    }
}
