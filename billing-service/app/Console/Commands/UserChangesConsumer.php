<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use Psr\Log\LoggerInterface;

class UserChangesConsumer extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger
    )
    {
        parent::__construct();
    }

    protected $signature = "consume:users-changes";

    public function handle()
    {
        $consumer = Kafka::createConsumer(['users-stream', 'users-role'])
            ->withConsumerGroupId(env('APP_NAME'))
            ->withHandler(function(KafkaConsumerMessage $message) {
                $body = $message->getBody();

                $this->info(print_r($body, true));
                $this->logger->debug($body);

                switch ([$body['event_name'], $body['event_version']]) {
                    case ['UserCreated', '1']:
                    case ['UserUpdated', '1']:
                        $user = User::query()
                            ->where('public_id', $body['data']['public_id'])
                            ->first();

                        if ($user === null) {
                            User::query()->create([
                                'public_id' => $body['data']['public_id'],
                                'name' => $body['data']['name'],
                                'email' => $body['data']['email'],
                            ]);
                        } else {
                            // если пользователь уже был создан как заглушка
                            $user->fill([
                                'name' => $body['data']['name'],
                                'email' => $body['data']['email'],
                            ]);
                            $user->save();
                        }
                        break;

                    case ['UserRoleChanged', '1']:
                        $user = User::query()
                            ->where('public_id', $body['data']['public_id'])
                            ->first();

                        // Если пользователь еще не создан, создаем его как заглушку
                        if ($user === null) {
                            User::query()->create([
                                'public_id' => $body['data']['public_id'],
                                'role' => UserRole::from($body['data']['role']),
                                'active' => false,
                            ]);
                        } else {
                            $user->role = UserRole::from($body['data']['role']);
                            $user->save();
                        }
                        break;
                }
            })
            ->build();

        $consumer->consume();
    }
}
