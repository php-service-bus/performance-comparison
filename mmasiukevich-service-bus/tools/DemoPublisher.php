<?php

declare(strict_types = 1);

use Amp\Promise;
use function Amp\Promise\wait;
use function Amp\call;
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
     * @param Message     $message
     * @param string|null $topic
     * @param string|null $routingKey
     *
     * @return Promise
     */
    public function sendMessage(Message $message, ?string $topic = null, ?string $routingKey = null): Promise
    {
        return call(
            function(Message $message, ?string $topic, ?string $routingKey): \Generator
            {
                $topic      = $topic ?? (string) \getenv('SENDER_DESTINATION_TOPIC');
                $routingKey = $routingKey ?? (string) \getenv('SENDER_DESTINATION_TOPIC_ROUTING_KEY');

                /** @var Transport $transport */
                $transport = yield from $this->transport();

                yield $transport->send(
                    new OutboundPackage(
                        $this->encoder->encode($message),
                        [Transport::SERVICE_BUS_TRACE_HEADER => uuid()],
                        new AmqpTransportLevelDestination($topic, $routingKey)
                    )
                );
            },
            $message, $topic, $routingKey
        );
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @return \Generator
     */
    private function transport(): \Generator
    {
        if(null === $this->transport)
        {
            $this->transport = new BunnyRabbitMqTransport(
                new AmqpConnectionConfiguration(\getenv('TRANSPORT_CONNECTION_DSN'))
            );

            yield $this->transport->connect();

            $mainExchange = AmqpExchange::direct((string) \getenv('TRANSPORT_TOPIC'), true);
            $mainQueue    = AmqpQueue::default((string) \getenv('TRANSPORT_QUEUE'), true);

            yield $this->transport->createQueue(
                $mainQueue,
                new QueueBind(
                    $mainExchange,
                    (string) \getenv('TRANSPORT_ROUTING_KEY'))
            );
        }

        return $this->transport;
    }
}
