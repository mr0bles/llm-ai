<?php

namespace Tests\Unit\Shared\Infrastructure\Services;

use App\Shared\Infrastructure\Services\RabbitMQService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Tests\TestCase;

class RabbitMQServiceTest extends TestCase
{
    private RabbitMQService $service;
    private $connectionMock;
    private $channelMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channelMock = $this->createMock(AMQPChannel::class);
        $this->connectionMock = $this->createMock(AMQPStreamConnection::class);

        $this->connectionMock->method('channel')
            ->willReturn($this->channelMock);

        $this->service = new RabbitMQService();
    }

    public function test_publish_message(): void
    {
        $exchange = 'test_exchange';
        $routingKey = 'test_key';
        $data = ['message' => 'test'];

        $this->channelMock->expects($this->once())
            ->method('exchange_declare')
            ->with($exchange, 'direct', false, true, false);

        $this->channelMock->expects($this->once())
            ->method('basic_publish')
            ->with(
                $this->callback(function ($message) use ($data) {
                    return $message->getBody() === json_encode($data);
                }),
                $exchange,
                $routingKey
            );

        $this->service->publish($exchange, $routingKey, $data);
    }

    public function test_consume_messages(): void
    {
        $queue = 'test_queue';
        $exchange = 'test_exchange';
        $routingKey = 'test_key';
        $callback = function ($message) {};

        $this->channelMock->expects($this->once())
            ->method('queue_declare')
            ->with($queue, false, true, false, false);

        $this->channelMock->expects($this->once())
            ->method('exchange_declare')
            ->with($exchange, 'direct', false, true, false);

        $this->channelMock->expects($this->once())
            ->method('queue_bind')
            ->with($queue, $exchange, $routingKey);

        $this->channelMock->expects($this->once())
            ->method('basic_consume')
            ->with($queue, '', false, true, false, false, $callback);

        $this->channelMock->expects($this->once())
            ->method('is_consuming')
            ->willReturn(false);

        $this->service->consume($queue, $exchange, $routingKey, $callback);
    }
} 