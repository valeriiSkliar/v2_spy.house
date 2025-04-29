<?php

namespace App\Console\Commands\Queues;

use Illuminate\Console\Command;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;
use Illuminate\Queue\QueueManager;
use PhpAmqpLib\Exception\AMQPIOException;

class ListQueuesCommand extends Command
{
    protected $signature = 'queues:list';
    protected $description = 'List all RabbitMQ queues and their message counts';

    public function handle(QueueManager $queueManager)
    {
        try {
            /** @var RabbitMQQueue $queue */
            $queue = $queueManager->connection('rabbitmq');
            $channel = $queue->getChannel();
        } catch (AMQPIOException $e) {
            $this->error('Could not connect to RabbitMQ.');
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        $queues = array_keys(config('queue.connections.rabbitmq.queues'));

        $headers = ['Queue Name', 'Messages'];
        $rows = [];

        foreach ($queues as $queueName) {
            try {
                $queueInfo = $channel->queue_declare(
                    $queueName,
                    true,  // passive - only check if exists
                    false, // durable
                    false, // exclusive
                    false  // auto delete
                );

                $rows[] = [$queueName, $queueInfo[1] ?? 0];
            } catch (\Exception $e) {
                $rows[] = [$queueName, 'Queue not found'];
            }
        }

        $this->table($headers, $rows);

        return Command::SUCCESS;
    }
}
