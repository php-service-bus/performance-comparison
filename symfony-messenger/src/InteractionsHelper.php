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

use Symfony\Component\Messenger\MessageBus;
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
     * @var TransportConnection
     */
    private $transport;

    /**
     * @param MessageBus $bus
     *
     * @throws \Throwable
     */
    public function __construct(MessageBus $bus)
    {
        $this->bus        = $bus;
        $this->transport  = TransportConnection::fromDsn(\getenv('MESSENGER_TRANSPORT_DSN'));
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

        $queue = $this->transport->queue('messages');

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