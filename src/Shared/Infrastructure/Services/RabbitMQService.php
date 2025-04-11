<?php

namespace App\Shared\Infrastructure\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    private AMQPStreamConnection $connection;
    private \PhpAmqpLib\Channel\AMQPChannel $channel;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            config('queue.connections.rabbitmq.host'),
            config('queue.connections.rabbitmq.port'),
            config('queue.connections.rabbitmq.user'),
            config('queue.connections.rabbitmq.password'),
            config('queue.connections.rabbitmq.vhost')
        );

        $this->channel = $this->connection->channel();
    }

    public function publish(string $exchange, string $routingKey, array $data): void
    {
        $this->channel->exchange_declare($exchange, 'direct', false, true, false);
        
        $message = new AMQPMessage(
            json_encode($data),
            ['content_type' => 'application/json']
        );

        $this->channel->basic_publish($message, $exchange, $routingKey);
    }

    public function consume(string $queue, string $exchange, string $routingKey, callable $callback): void
    {
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->exchange_declare($exchange, 'direct', false, true, false);
        $this->channel->queue_bind($queue, $exchange, $routingKey);

        $this->channel->basic_consume($queue, '', false, true, false, false, $callback);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
} 