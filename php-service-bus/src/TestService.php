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
use ServiceBus\Common\Endpoint\DeliveryOptions;
use ServiceBus\Context\KernelContext;
use ServiceBus\Endpoint\DefaultDeliveryOptions;
use ServiceBus\Services\Annotations\CommandHandler;
use ServiceBus\Services\Annotations\EventListener;
use ServiceBus\Storage\Common\DatabaseAdapter;
use ServiceBus\Storage\Common\QueryExecutor;
use function ServiceBus\Storage\Sql\insertQuery;

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
     *
     * @param StoreCustomerCommand $command
     * @param KernelContext        $context
     * @param DatabaseAdapter      $adapter
     *
     * @return Promise
     *
     * @throws \Throwable
     */
    public function handle(
        StoreCustomerCommand $command,
        KernelContext $context,
        DatabaseAdapter $adapter
    ): Promise
    {
        $deliveryOptions = $this->deliveryOptions;

        return $adapter->transactional(
            static function(QueryExecutor $executor) use ($command, $context, $deliveryOptions): \Generator
            {
                $builder = insertQuery('customers', [
                        'id'    => $command->id,
                        'name'  => $command->name,
                        'email' => $command->email
                    ]
                );

                $compiledQuery = $builder->compile();

                yield $executor->execute($compiledQuery->sql(), $compiledQuery->params());
                yield $context->delivery(new CustomerStored($command->id), $deliveryOptions);
            }
        );
    }

    /**
     * @EventListener(validate=false)
     *
     * @param CustomerStored $event
     * @param KernelContext  $context
     *
     * @return void
     */
    public function whenCustomerStored(CustomerStored $event, KernelContext $context): void
    {
        $context->logContextMessage('Registered with id "{id}"', [
                'id'   => $event->id,
                'date' => \date('Y-m-d H:i:s')
            ]
        );
    }
}
