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
use Symfony\Component\Messenger\Transport\AmqpExt\Connection as TransportConnection;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 *
 */
final class InteractionsHelper
{
    /**
     * @var MessageBusInterface
     */
    private $bus;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var TransportConnection
     */
    private $transport;

    /**
     * @param MessageBusInterface $bus
     * @param Connection          $connection
     *
     * @throws \Throwable
     */
    public function __construct(MessageBusInterface $bus, Connection $connection)
    {
        $this->bus        = $bus;
        $this->connection = $connection;
        $this->transport  = TransportConnection::fromDsn(\getenv('MESSENGER_TRANSPORT_DSN'));
    }

    /**
     * @return void
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createSchema(): void
    {
        $this->connection->query(
            <<<EOT
CREATE TABLE IF NOT EXISTS customers
(
    id uuid PRIMARY KEY,
    name varchar NOT NULL,
    email varchar NOT NULL
);
EOT
        );
    }

    /**
     * @return void
     *
     * @throws \Throwable
     */
    public function createQueue(): void
    {
        $exchange = $this->transport->exchange();
        $exchange->setName('messages');
        $exchange->setType(\AMQP_EX_TYPE_FANOUT);
        $exchange->setFlags(\AMQP_DURABLE);

        $exchange->declareExchange();

        $queue = $this->transport->queue();

        $queue->setName('messages');
        $queue->setFlags(\AMQP_DURABLE);

        $queue->declareQueue();
        $queue->bind($exchange->getName(), '');

    }

    public function dispatch(object $message): void
    {
        $this->bus->dispatch($message);
    }
}