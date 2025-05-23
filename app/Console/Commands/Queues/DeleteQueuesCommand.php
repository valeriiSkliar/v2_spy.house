<?php

namespace App\Console\Commands\Queues;

use Illuminate\Console\Command;
use Illuminate\Queue\QueueManager;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

class DeleteQueuesCommand extends Command
{
    protected $signature = 'queues:delete';

    protected $description = 'Delete RabbitMQ queues';

    public function handle(QueueManager $queueManager)
    {
        /** @var RabbitMQQueue $queue */
        $queue = $queueManager->connection('rabbitmq');
        $channel = $queue->getChannel();
        $queues = array_keys(config('queue.connections.rabbitmq.queues'));

        // $queues = [
        //     'default',
        //     'collect-ads',
        //     'push-house-ads',
        //     'delayed',
        //     'mail'
        // ];

        foreach ($queues as $queueName) {
            try {
                $channel->queue_delete($queueName);
                $this->info("Queue '$queueName' deleted successfully");
            } catch (\Exception $e) {
                $this->error("Failed to delete queue '$queueName': ".$e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
