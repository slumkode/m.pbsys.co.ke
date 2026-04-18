<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

class RabbitMqService
{
    protected $connection;
    protected $channel;

    public function isAvailable()
    {
        return class_exists(AMQPStreamConnection::class) && class_exists(AMQPMessage::class);
    }

    public function publish($queue, array $payload)
    {
        $channel = $this->channel();
        $this->declareQueue($queue);

        $message = new AMQPMessage(json_encode($payload), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $channel->basic_publish($message, '', $queue);
    }

    public function pop($queue)
    {
        $channel = $this->channel();
        $this->declareQueue($queue);

        return $channel->basic_get($queue);
    }

    protected function channel()
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException('RabbitMQ PHP client is not installed. Run composer require php-amqplib/php-amqplib:^3.7.');
        }

        if (! $this->connection) {
            $this->connection = new AMQPStreamConnection(
                config('rabbitmq.host'),
                config('rabbitmq.port'),
                config('rabbitmq.user'),
                config('rabbitmq.password'),
                config('rabbitmq.vhost')
            );
        }

        if (! $this->channel) {
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }

    protected function declareQueue($queue)
    {
        $this->channel()->queue_declare($queue, false, true, false, false);
    }

    public function __destruct()
    {
        if ($this->channel) {
            $this->channel->close();
        }

        if ($this->connection) {
            $this->connection->close();
        }
    }
}
