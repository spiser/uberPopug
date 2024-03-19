<?php
namespace App\Services;

use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\MessageBatch;
use Modules\SchemaRegistry\SchemaRegistry;

class KafkaService
{
    public function __construct(
        private readonly SchemaRegistry $schemaRegistry
    ) {
    }

    public function produce(string $topic, array $data)
    {
        $this->schemaRegistry->validateEvent($data, $data['event_name'], $data['event_version']);

        Kafka::publishOn($topic)
            ->withMessage(new Message(
                topicName: $topic,
                body: $data
            ))
            ->withDebugEnabled()
            ->send();
    }

    public function produceBatch(string $topic, array $batchData)
    {
        $messageBatch = new MessageBatch();

        foreach ($batchData as $data) {
            $this->schemaRegistry->validateEvent($data, $data['event_name'], $data['event_version']);

            $messageBatch->push(
                new Message(body: $data)
            );
        }

        Kafka::publishOn($topic)
            ->withDebugEnabled()
            ->sendBatch($messageBatch);
    }
}
