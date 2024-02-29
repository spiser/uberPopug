<?php

namespace App\Console\Commands;

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

    protected $signature = "consume:user-changes";

    public function handle()
    {
        $consumer = Kafka::createConsumer(['users-stream', 'users-role'])
            ->withAutoCommit()
            ->withHandler(function(KafkaConsumerMessage $message) {
                $body = $message->getBody();

                $this->logger->debug($body);

                switch ($body['event_name']) {
                    case 'UserCreated':
                        $user = User::query()
                            ->where('public_id', $body['public_id'])
                            ->first();

                        // если пользователь уже был создан как заглушка
                        if ($user === null) {
                            User::query()->create([
                                'public_id' => $body['public_id'],
                                'name' => $body['data']['name'],
                                'email' => $body['data']['email'],
                            ]);
                        } else {
                            $user->fill([
                                'name' => $body['data']['name'],
                                'email' => $body['data']['email'],
                            ]);
                            $user->save();
                        }
                        break;

                    case 'UserUpdated':
                        $user = User::query()
                            ->where('public_id', $body['public_id'])
                            ->firstOrFail();

                        $user->fill([
                            'name' => $body['data']['name'],
                            'email' => $body['data']['email'],
                        ]);
                        $user->save();
                        break;

                    case 'UserRoleChanged':
                        $user = User::query()
                            ->where('public_id', $body['public_id'])
                            ->first();

                        // Если пользователь еще не создан, создаем его как заглушку
                        if ($user === null) {
                            User::query()->create([
                                'public_id' => $body['public_id'],
                                'role' => $body['data']['role'],
                            ]);
                        } else {
                            $user->role = $body['data']['role'];
                            $user->save();
                        }
                        break;

                    case 'UserDeleted':
                        $user = User::query()
                            ->where('public_id', $body['public_id'])
                            ->firstOrFail();
                        $user->active = false;
                        $user->save();
                        break;
                }
            })
            ->build();

        $consumer->consume();
    }
}
