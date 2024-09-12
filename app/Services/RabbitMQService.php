<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    protected AMQPStreamConnection $connection;
    protected AMQPChannel $channel;
    private int $prefetchCount = 100;
    private string $queueName = 'source_data_queue';

    /**
     * Initialize connection and declare queues.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST'),
            env('RABBITMQ_PORT'),
            env('RABBITMQ_USER'),
            env('RABBITMQ_PASS')
        );
        $this->channel = $this->connection->channel();

        $this->channel->queue_declare(
            queue: $this->queueName,
            passive: false,
            durable: true,
            exclusive: false,
            auto_delete: false
        );

        $this->declareQueueWithDLX($this->queueName);

        $this->channel->confirm_select();
        $this->channel->basic_qos(prefetch_size: 0, prefetch_count: $this->prefetchCount, a_global: null);
    }

    /**
     * Publish messages to RabbitMQ queue in batches.
     *
     * @param string $queueName
     * @param array $records
     * @param int $batchSize
     * @throws \Exception
     */
    public function publishMessagesInBatch(string $queueName, array $records, int $batchSize): void
    {
        $messages = [];
        foreach ($records as $record) {
            $messageId = $record['id']; // Secure enough for this task

            $msg = new AMQPMessage(
                json_encode($record),
                [
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'message_id' => $messageId
                ]
            );

            $messages[] = $msg;

            if (count($messages) === $batchSize) {
                $this->publishBatch($queueName, $messages);
                $messages = [];
            }
        }

        if (!empty($messages)) {
            $this->publishBatch($queueName, $messages);
        }
    }

    /**
     * Publish a batch of messages to the queue.
     *
     * @param string $queueName
     * @param array $messages
     */
    private function publishBatch(string $queueName, array $messages): void
    {
        foreach ($messages as $msg) {
            $this->channel->batch_basic_publish($msg, '', $queueName);
        }
        $this->channel->publish_batch();
        $this->channel->wait_for_pending_acks();
    }

    /**
     * Declare a queue with Dead Letter Exchange and TTL settings.
     *
     * @param string $queueName
     */
    public function declareQueueWithDLX(string $queueName): void
    {
        $args = [
            'x-dead-letter-exchange' => ['S', 'dlx_exchange'],
            'x-dead-letter-routing-key' => ['S', 'failed_queue'],
            'x-message-ttl' => ['I', 15000],
        ];

        $this->channel->queue_declare($queueName, false, true, false, false, false, $args);
        $this->channel->queue_declare('failed_queue', false, true, false, false);
    }

    public function consumeFailedMessages(): void
    {
        $this->channel->basic_consume(
            'failed_queue',
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) {
                Log::error("Failed message reprocessing: " . $msg->getBody());
                $this->channel->basic_ack($msg->get('delivery_tag'));
            }
        );

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    /**
     * Close connection to RabbitMQ.
     * @throws \Exception
     */
    public function close(): void
    {
        $this->channel->close();
        $this->connection->close();
    }
}
