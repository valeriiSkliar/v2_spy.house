<?php

namespace App\Console\Commands\Queues;

use Illuminate\Console\Command;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;
use Illuminate\Queue\QueueManager;

class CreateQueuesCommand extends Command
{
    protected $signature = 'queues:create';
    protected $description = 'Create RabbitMQ queues';

    public function handle(QueueManager $queueManager)
    {
        /** @var RabbitMQQueue $queue */
        $queue = $queueManager->connection('rabbitmq');
        $channel = $queue->getChannel();
        $queues = array_keys(config('queue.connections.rabbitmq.queues'));


        foreach ($queues as $queueName) {
            $channel->queue_declare(
                $queueName,
                false, // passive
                true,  // durable
                false, // exclusive
                false  // auto delete
            );
            $this->info("Queue '$queueName' created successfully");
        }

        return Command::SUCCESS;
    }
}
