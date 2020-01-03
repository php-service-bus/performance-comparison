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

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\MessageBusInterface;

final class StoreCustomerHandler
{
    /**
     * @var MessageBusInterface
     */
    private $bus;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(MessageBusInterface $bus, Connection $connection)
    {
        $this->bus        = $bus;
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Throwable
     */
    public function __invoke(StoreCustomer $message)
    {
        $this->connection->beginTransaction();

        try
        {
            $statement = $this->connection->prepare('INSERT INTO customers (id, name, email) VALUES (?, ?, ?)');
            $statement->execute([$message->id, $message->name, $message->email]);

            $this->bus->dispatch(new CustomerStored($message->id));

            $this->connection->commit();
        }
        catch(\Throwable $throwable)
        {
            $this->connection->rollBack();

            throw $throwable;
        }
    }
}
