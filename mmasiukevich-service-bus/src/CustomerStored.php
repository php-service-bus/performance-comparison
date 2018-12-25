<?php

/**
 * PHP Telegram Bot Api implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */
declare(strict_types = 1);

namespace App;

use Desperado\ServiceBus\Common\Contract\Messages\Event;

/**
 *
 */
final class CustomerStored implements Event
{
    /**
     * @var string
     */
    public $id;

    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }
}
