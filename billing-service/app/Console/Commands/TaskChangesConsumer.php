<?php

namespace App\Console\Commands;

use App\Enums\TransactionType;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use Psr\Log\LoggerInterface;

class TaskChangesConsumer extends Command
{
    public function __construct(
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
            ->enableBatching()
            ->withBatchSizeLimit(10)
            ->withCommitBatchSize(10)
            ->withDlq()
            ->withHandler(function(Collection $collection) {
                /** @var KafkaConsumerMessage $message*/
                foreach ($collection as $message)
                $body = $message->getBody();

                $this->info(print_r($body, true));
                $this->logger->debug($body);

                switch ([$body['event_name'], $body['event_version']]) {
                    case ['TaskCreated', '1']:
                        Task::query()->upsert(
                            values: [
                                'public_id' => $body['data']['public_id'],
                                'description' => $body['data']['description'],
                                'assigned_cost' => rand(10, 20),
                                'completed_cost' =>  rand(20, 40),
                            ],
                            uniqueBy: 'public_id'
                        );
                        break;
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

                            Transaction::query()->create([
                                'user_id' => $user->id,
                                'task_id' => $task->id,
                                'type' => TransactionType::task,
                                'debit' => 0,
                                'credit' => $task->assigned_cost
                            ]);

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

                            Transaction::query()->create([
                                'user_id' => $user->id,
                                'task_id' => $task->id,
                                'type' => TransactionType::task,
                                'debit' => $task->completed_cost,
                                'credit' => 0
                            ]);

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
