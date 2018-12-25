<?php

declare(strict_types = 1);

use function Amp\Promise\wait;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\MessageEncoder;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony\SymfonyMessageSerializer;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpConnectionConfiguration;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpTransportLevelDestination;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\BunnyRabbitMQ\BunnyRabbitMqTransport;
use Desperado\ServiceBus\Infrastructure\Transport\Package\OutboundPackage;
use Desperado\ServiceBus\Infrastructure\Transport\Transport;
use Symfony\Component\Dotenv\Dotenv;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpExchange;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpQueue;
use Desperado\ServiceBus\Infrastructure\Transport\QueueBind;

/**
 * Publisher example
 *
 * Attention: for example only. Do not use this code.
 */
final class DemoPublisher
{
    /**
     * @var Transport|null
     */
    private $transport;
    /**
     * @var MessageEncoder
     */
    private $encoder;

    /**
     * @param string $envPath
     */
    public function __construct(string $envPath)
    {
        (new Dotenv())->load($envPath);
        $this->encoder = new SymfonyMessageSerializer();
    }

    /**
     * Send message to queue
     *
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param Message     $message
     * @param string|null $topic
     * @param string|null $routingKey
     *
     * @return void
     */
    public function sendMessage(Message $message, ?string $topic = null, ?string $routingKey = null): void
    {
        $topic      = $topic ?? (string) \getenv('SENDER_DESTINATION_TOPIC');
        $routingKey = $routingKey ?? (string) \getenv('SENDER_DESTINATION_TOPIC_ROUTING_KEY');
        /** @noinspection PhpUnhandledExceptionInspection */
        wait(
            $this->transport()->send(
                new OutboundPackage(
                    $this->encoder->encode($message),
                    [Transport::SERVICE_BUS_TRACE_HEADER => uuid()],
                    new AmqpTransportLevelDestination($topic, $routingKey)
                )
            )
        );
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @return Transport
     */
    private function transport(): Transport
    {
        if(null === $this->transport)
        {
            $this->transport = new BunnyRabbitMqTransport(
                new AmqpConnectionConfiguration(\getenv('TRANSPORT_CONNECTION_DSN'))
            );

            /** @noinspection PhpUnhandledExceptionInspection */
            wait($this->transport->connect());

            $mainExchange = AmqpExchange::direct((string) \getenv('TRANSPORT_TOPIC'), true);
            $mainQueue    = AmqpQueue::default((string) \getenv('TRANSPORT_QUEUE'), true);

            $promise = $this->transport->createQueue(
                $mainQueue,
                new QueueBind(
                    $mainExchange,
                    (string) \getenv('TRANSPORT_ROUTING_KEY'))
            );

            wait($promise);
        }

        return $this->transport;
    }
}
