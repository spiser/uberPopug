<?php

namespace App\Console\Commands;

use App\Enums\TransactionType;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use App\Services\KafkaService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class TaskChangesConsumer extends Command
{
    public function __construct(
        private readonly KafkaService $kafkaService,
        private readonly LoggerInterface $logger
    )
    {
        parent::__construct();
    }

    protected $signature = "consume:tasks-changes";

    public function handle()
    {
        $consumer = Kafka::createConsumer(['tasks-stream', 'tasks-flow'])
            ->withConsumerGroupId(env('APP_NAME'))
            ->withHandler(function(KafkaConsumerMessage $message) {
                $body = $message->getBody();

                $this->info(print_r($body, true));
                $this->logger->debug($body);

                switch ([$body['event_name'], $body['event_version']]) {
                    case ['TaskCreated', '1']:
                        $task = Task::query()->create([
                            'public_id' => $body['data']['public_id'],
                            'description' => $body['data']['description'],
                            'assigned_cost' => rand(10, 20),
                            'completed_cost' => rand(20, 40),
                        ]);

                        $this->kafkaService->produce(
                            topic: 'tasks-costs-stream',
                            data: [
                                'event_id' => Uuid::uuid6()->toString(),
                                'event_version' => '1',
                                'event_name' => 'TaskCostsCreated',
                                'event_time' => time(),
                                'producer' => env('APP_NAME'),
                                'data' => [
                                    'public_task_id' => $task->public_id,
                                    'assigned_cost' => $task->assigned_cost,
                                    'completed_cost' => $task->completed_cost,
                                ]
                            ]
                        );

                        break;
                    case ['TaskAdded', '1']:
                    case ['TaskAssigned', '1']:
                        DB::beginTransaction();

                        try {
                            $user = User::query()
                                ->where('public_id', $body['data']['assigned_user_id'])
                                ->lockForUpdate()
                                ->firstOrFail();

                            $task = Task::query()
                                ->where('public_id', $body['data']['public_id'])
                                ->firstOrFail();

                            $user->balance = $user->balance - $task->assigned_cost;
                            $user->save();

                            $transaction = Transaction::query()->create([
                                'user_id' => $user->id,
                                'task_id' => $task->id,
                                'type' => TransactionType::enrolment,
                                'debit' => 0,
                                'credit' => $task->assigned_cost
                            ]);

                            $this->kafkaService->produce(
                                topic: 'transactions',
                                data: [
                                    'event_id' => Uuid::uuid6()->toString(),
                                    'event_version' => '1',
                                    'event_name' => 'TransactionEnrolmentAdded',
                                    'event_time' => time(),
                                    'producer' => env('APP_NAME'),
                                    'data' => [
                                        'public_id' => $transaction->public_id,
                                        'credit' => $transaction->credit,
                                        'created_at' => $transaction->created_at,
                                    ]
                                ]
                            );

                            DB::commit();
                        } catch (\Exception $exception) {
                            DB::rollBack();
                            throw $exception;
                        }
                        break;
                    case ['TaskCompleted', '1']:
                        DB::beginTransaction();

                        try {
                            $user = User::query()
                                ->where('public_id', $body['data']['assigned_user_id'])
                                ->lockForUpdate()
                                ->firstOrFail();

                            $task = Task::query()
                                ->where('public_id', $body['data']['public_id'])
                                ->firstOrFail();

                            $user->balance = $user->balance + $task->completed_cost;
                            $user->save();

                            $transaction = Transaction::query()->create([
                                'user_id' => $user->id,
                                'task_id' => $task->id,
                                'type' => TransactionType::withdrawal,
                                'debit' => $task->completed_cost,
                                'credit' => 0
                            ]);

                            $this->kafkaService->produce(
                                topic: 'transactions',
                                data: [
                                    'event_id' => Uuid::uuid6()->toString(),
                                    'event_version' => '1',
                                    'event_name' => 'TransactionWithdrawalAdded',
                                    'event_time' => time(),
                                    'producer' => env('APP_NAME'),
                                    'data' => [
                                        'public_id' => $transaction->public_id,
                                        'debit' => $transaction->debit,
                                        'created_at' => $transaction->created_at,
                                    ]
                                ]
                            );

                            DB::commit();
                        } catch (\Exception $exception) {
                            DB::rollBack();
                            throw $exception;
                        }
                        break;
                }
            })
            ->build();

        $consumer->consume();
    }
}
