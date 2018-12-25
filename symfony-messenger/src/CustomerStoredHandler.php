<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation) demo
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace App;


use Psr\Log\LoggerInterface;

final class CustomerStoredHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface     $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    public function __invoke(CustomerStored $message)
    {
        $this->logger->debug('Registered with id "{id}"', [
                'id'   => $message->id,
                'date' => \date('Y-m-d H:i:s')
            ]
        );
    }
}
