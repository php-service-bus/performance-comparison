<?php

declare(strict_types = 1);

namespace App;

use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
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

    public function __construct(MessageBus $bus)
    {
        $this->bus        = $bus;
        $this->transport  = Connection::fromDsn(\getenv('MESSENGER_TRANSPORT_DSN'));
    }

    public function createQueue(): void
    {
        $exchange = $this->transport->exchange();
        $exchange->setName('messages');
        $exchange->setType(\AMQP_EX_TYPE_DIRECT);
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