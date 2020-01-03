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

use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use ServiceBus\Endpoint\Options\DefaultDeliveryOptions;
use ServiceBus\Services\Annotations\CommandHandler;
use ServiceBus\Services\Annotations\EventListener;
use ServiceBus\Storage\Common\DatabaseAdapter;
use ServiceBus\Storage\Common\QueryExecutor;

/**
 *
 */
final class TestService
{
    /**
     * @var DeliveryOptions
     */
    private $deliveryOptions;

    public function __construct()
    {
        $this->deliveryOptions = DefaultDeliveryOptions::nonPersistent();
    }


    /**
     * @CommandHandler(validate=false)
     */
    public function handle(
        StoreCustomerCommand $command,
        ServiceBusContext $context,
        DatabaseAdapter $adapter
    ): Promise
    {
        return $adapter->transactional(
            function(QueryExecutor $executor) use ($command, $context): \Generator
            {
                yield $executor->execute('INSERT INTO customers (id, name, email) VALUES (?, ?, ?)', [
                    $command->id,
                    $command->name,
                    $command->email

                ]);

                yield $context->delivery(new CustomerStored($command->id), $this->deliveryOptions);
            }
        );
    }

    /**
     * @EventListener(validate=false)
     */
    public function whenCustomerStored(CustomerStored $event, ServiceBusContext $context): void
    {
        $context->logContextMessage('Registered with id "{id}"', [
                'id'   => $event->id,
                'date' => \date('Y-m-d H:i:s')
            ]
        );
    }
}
