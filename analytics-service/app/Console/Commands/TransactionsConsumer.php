<?php

namespace App\Console\Commands;

use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\Transaction;
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

    protected $signature = "consume:billing-transactions";

    public function handle()
    {
        $consumer = Kafka::createConsumer(['transactions'])
            ->withConsumerGroupId(env('APP_NAME'))
            ->withHandler(function(KafkaConsumerMessage $message) {
                $body = $message->getBody();

                $this->info(print_r($body, true));
                $this->logger->debug($body);

                switch ([$body['event_name'], $body['event_version']]) {
                    case ['TransactionEnrolmentAdded', '1']:
                        Transaction::query()->create([
                            'public_id' => $body['data']['public_id'],
                            'type'      => 'enrolment',
                            'debit'     => $body['data']['debit'],
                            'created_at' => $body['data']['created_at'],
                            'credit' => 0
                        ]);
                        break;
                    case ['TransactionWithdrawalAdded', '1']:
                        Transaction::query()->create([
                            'public_id' => $body['data']['public_id'],
                            'type'      => 'withdrawal',
                            'credit'    => $body['data']['debit'],
                            'created_at' => $body['data']['created_at'],
                            'debit' => 0
                        ]);
                        break;
                }
            })
            ->build();

        $consumer->consume();
    }
}
