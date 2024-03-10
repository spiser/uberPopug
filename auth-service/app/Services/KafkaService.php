<?php
namespace App\Services;

use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
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
}
