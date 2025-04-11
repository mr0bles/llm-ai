<?php

namespace App\Shared\Infrastructure\Console\Commands;

use App\Shared\Infrastructure\Services\RabbitMQService;
use Illuminate\Console\Command;

class ConsumeRabbitMQCommand extends Command
{
    protected $signature = 'rabbitmq:consume {queue} {exchange} {routing_key}';
    protected $description = 'Consume messages from RabbitMQ';

    public function handle(RabbitMQService $rabbitmq): void
    {
        $queue = $this->argument('queue');
        $exchange = $this->argument('exchange');
        $routingKey = $this->argument('routing_key');

        $this->info("Starting to consume messages from queue: {$queue}");

        $rabbitmq->consume($queue, $exchange, $routingKey, function ($message) {
            $this->info("Received message: " . $message->body);
            // Aquí puedes procesar el mensaje según tus necesidades
            $message->ack();
        });
    }
} 