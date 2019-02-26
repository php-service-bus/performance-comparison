<?php

declare(strict_types = 1);

use function Amp\call;
use Amp\Promise;
use function ServiceBus\Common\uuid;
use ServiceBus\MessageSerializer\MessageEncoder;
use ServiceBus\MessageSerializer\Symfony\SymfonyMessageSerializer;
use ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use ServiceBus\Transport\Amqp\AmqpExchange;
use ServiceBus\Transport\Amqp\AmqpQueue;
use ServiceBus\Transport\Amqp\AmqpTransportLevelDestination;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Common\QueueBind;
use ServiceBus\Transport\Common\Transport;
use ServiceBus\Transport\PhpInnacle\PhpInnacleTransport;
use Symfony\Component\Dotenv\Dotenv;

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
     *
     * @throws \Throwable
     */
    public function __construct(string $envPath)
    {
        (new Dotenv())->load($envPath);

        $this->encoder = new SymfonyMessageSerializer();
    }

    /**
     * Send message to queue
     *
     * @param object     $message
     * @param string|null $topic
     * @param string|null $routingKey
     *
     * @return Promise
     */
    public function sendMessage(object $message, ?string $topic = null, ?string $routingKey = null): Promise
    {
        return call(
            function(object $message, ?string $topic, ?string $routingKey): \Generator
            {
                $topic      = $topic ?? (string) \getenv('SENDER_DESTINATION_TOPIC');
                $routingKey = $routingKey ?? (string) \getenv('SENDER_DESTINATION_TOPIC_ROUTING_KEY');

                /** @var Transport $transport */
                $transport = yield from $this->transport();

                yield $transport->send(
                    OutboundPackage::create(
                        $this->encoder->encode($message),
                        [],
                        new AmqpTransportLevelDestination($topic, $routingKey),
                        uuid()
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
     *
     * @throws \Throwable
     */
    private function transport(): \Generator
    {
        if(null === $this->transport)
        {
            $this->transport = new PhpInnacleTransport(
                new AmqpConnectionConfiguration(\getenv('TRANSPORT_CONNECTION_DSN'))
            );

            yield $this->transport->connect();

            $mainExchange = AmqpExchange::direct((string) \getenv('TRANSPORT_TOPIC'), true);
            $mainQueue    = AmqpQueue::default((string) \getenv('TRANSPORT_QUEUE'), true);

            yield $this->transport->createQueue(
                $mainQueue,
                QueueBind::create($mainExchange, (string) \getenv('TRANSPORT_ROUTING_KEY'))
            );
        }

        return $this->transport;
    }
}
