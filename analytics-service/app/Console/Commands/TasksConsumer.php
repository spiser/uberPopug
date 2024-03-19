<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use Illuminate\Console\Command;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use Psr\Log\LoggerInterface;

class TransactionsConsumer extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger
    )
    {
        parent::__construct();
    }

    protected $signature = "consume:tasks";

    public function handle()
    {
        $consumer = Kafka::createConsumer(['tasks-stream', 'tasks-flow', 'tasks-costs-stream'])
            ->withConsumerGroupId(env('APP_NAME'))
            ->withHandler(function(KafkaConsumerMessage $message) {
                $body = $message->getBody();

                $this->info(print_r($body, true));
                $this->logger->debug($body);

                switch ([$body['event_name'], $body['event_version']]) {
                    case ['TaskCreated', '1']:
                        $task = Task::query()
                            ->where('public_id', $body['data']['public_id'])
                            ->first();

                        if ($task === null) {
                            Task::query()->create([
                                'public_id' => $body['data']['public_id'],
                                'description' => $body['data']['description'],
                                'status' => 'processing',
                            ]);
                        } else {
                            $task->update([
                                'public_id' => $body['data']['public_id'],
                                'description' => $body['data']['description'],
                                'status' => 'processing',
                            ]);
                        }
                        break;
                    case ['TaskCostsCreated', '1']:
                        $task = Task::query()
                            ->where('public_id', $body['data']['public_id'])
                            ->first();

                        if ($task === null) {
                            Task::query()->create([
                                'public_id' => $body['data']['public_id'],
                                'description' => $body['data']['description'],
                                'assigned_cost' => $body['data']['assigned_cost'],
                                'completed_cost' => $body['data']['completed_cost'],
                            ]);
                        } else {
                            $task->update([
                                'assigned_cost' => $body['data']['assigned_cost'],
                                'completed_cost' => $body['data']['completed_cost'],
                            ]);
                        }
                        break;
                    case ['TaskCompleted', '1']:
                        $task = Task::query()
                            ->where('public_id', $body['data']['public_id'])
                            ->first();

                        if ($task === null) {
                            Task::query()->create([
                                'public_id' => $body['data']['public_id'],
                                'updated_at' => $body['data']['updated_at'],
                                'status' => 'done',
                            ]);
                        } else {
                            $task->update([
                                'public_id' => $body['data']['public_id'],
                                'updated_at' => $body['data']['updated_at'],
                                'status' => 'done',
                            ]);
                        }
                        break;
                }
            })
            ->build();

        $consumer->consume();
    }
}
