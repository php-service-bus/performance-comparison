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

use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\Endpoint\DeliveryOptions;
use Desperado\ServiceBus\Infrastructure\HttpClient\Data\HttpRequest;
use Desperado\ServiceBus\Infrastructure\HttpClient\HttpClient;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\insertQuery;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;
use Desperado\ServiceBus\Services\Annotations\CommandHandler;
use Desperado\ServiceBus\Services\Annotations\EventListener;

/**
 *
 */
final class TestService
{
    /**
     * @CommandHandler(validate=false)
     *
     * @param StoreCustomerCommand $command
     * @param KernelContext        $context
     * @param StorageAdapter       $adapter
     *
     * @return \Generator
     *
     * @throws \Throwable
     */
    public function handle(
        StoreCustomerCommand $command,
        KernelContext $context,
        StorageAdapter $adapter
    ): \Generator
    {
        $builder = insertQuery(
            'customers', [
                'id'    => $command->id,
                'name'  => $command->name,
                'email' => $command->email
            ]
        );

        $compiledQuery = $builder->compile();

        /** @var \Desperado\ServiceBus\Infrastructure\Storage\TransactionAdapter $transaction */
        $transaction = yield $adapter->transaction();

        try
        {
            yield $transaction->execute($compiledQuery->sql(), $compiledQuery->params());
            yield $context->delivery(new CustomerStored($command->id), DeliveryOptions::nonPersistent());
            yield $transaction->commit();
        }
        catch(\Throwable $throwable)
        {
            yield $transaction->rollback();

            throw $throwable;
        }
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
