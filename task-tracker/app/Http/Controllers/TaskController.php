<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use App\Services\KafkaService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\MessageBatch;
use Ramsey\Uuid\Uuid;

class TaskController extends Controller
{
    public function __construct(
        private readonly KafkaService $kafkaService
    ) {
    }

    public function list(Request $request)
    {
        $currentUser = $request->user();

        $query = Task::query()->orderBy('id');
        if ($currentUser->role === UserRole::worker)
            $query->where('user_id', $currentUser->id);

        return view('dashboard', [
            'tasks' => $query->get(),
            'currentUser' => $currentUser
        ]);
    }

    public function add(Request $request): View
    {
        $task = null;

        if ($request->id) {
            $task = Task::query()->findOrFail($request->id);
        }

        $users = User::query()
            ->where('role', UserRole::worker->value)
            ->where('active', true)
            ->get();

        return view('task.add', [
            'task' => $task,
            'users' => $users
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Task $task */
        $task = Task::query()->create([
            'description' => $request->description,
            'user_id' => $request->user_id,
        ]);

        $task->refresh();

        $this->kafkaService->produce(
            topic: 'tasks-stream',
            data: [
                'event_id' => Uuid::uuid6()->toString(),
                'event_version' => '1',
                'event_name' => 'TaskCreated',
                'event_time' => time(),
                'producer' => env('APP_NAME'),
                'data' => [
                    'public_id' => $task->public_id,
                    'description' => $task->description,
                    'status' => $task->status->value,
                    'assigned_user_id' => $task->user->public_id,
                ]
            ]
        );

        $this->kafkaService->produce(
            topic: 'tasks-flow',
            data: [
                'event_id' => Uuid::uuid6()->toString(),
                'event_version' => '1',
                'event_name' => 'TaskAdded',
                'event_time' => time(),
                'producer' => env('APP_NAME'),
                'data' => [
                    'public_id' => $task->public_id,
                    'description' => $task->description,
                    'status' => $task->status->value,
                    'assigned_user_id' => $task->user->public_id,
                ]
            ]
        );

        return Redirect::to('/dashboard');
    }

    public function done(Request $request): RedirectResponse
    {
        if ($request->user()->role !== UserRole::worker) {
            throw new Exception(
                sprintf('Только пользователь с ролью %s можно выполнить задачу', UserRole::worker->value)
            );
        }

        /** @var Task $task */
        $task = Task::query()->findOrFail($request->id);

        $task->status = TaskStatus::done;
        $task->save();

        $this->kafkaService->produce(
            topic: 'tasks-flow',
                data: [
                    'event_id' => Uuid::uuid6()->toString(),
                    'event_version' => '1',
                    'event_name' => 'TaskCompleted',
                    'event_time' => time(),
                    'producer' => env('APP_NAME'),
                    'data' => [
                        'public_id' => $task->public_id,
                        'assigned_user_id' => $task->user->public_id,
                    ]
                ]
            );

        return Redirect::to('/dashboard');
    }

    public function shuffle(Request $request)
    {
        if (
            $request->user()->role !== UserRole::manager &&
            $request->user()->role !== UserRole::admin
        ) {
            throw new Exception(
                sprintf('Только пользователь с ролью %s можно переназначить задачи', UserRole::manager->value)
            );
        }

        $users = User::query()
            ->where('role', UserRole::worker->value)
            ->where('active', true)
            ->get();

        $tasks = Task::query()
            ->where('status', TaskStatus::processing)
            ->get();

        if (count($tasks) > 1 && count($users) > 1) {
            $messageBatch = [];

            /** @var Task $task */
            foreach ($tasks as $task) {
                do {
                    $newUserId = $users->random()->id;
                } while ($task->user_id === $newUserId);

                $task->user_id = $newUserId;
                $task->save();

                $messageBatch[] = [
                    'event_id' => Uuid::uuid6()->toString(),
                    'event_version' => '1',
                    'event_name' => 'TaskAssigned',
                    'event_time' => time(),
                    'producer' => env('APP_NAME'),
                    'data' => [
                        'public_id' => $task->public_id,
                        'assigned_user_id' => $task->user->public_id,
                    ]
                ];
            }

            $this->kafkaService->produceBatch(
                topic: 'tasks-flow',
                batchData: $messageBatch
            );
        }

        return Redirect::to('/dashboard');
    }
}
