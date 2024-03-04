<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Producers\MessageBatch;

class TaskController extends Controller
{
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

    public function store(Request $request): RedirectResponse
    {
        /** @var Task $task */
        $task = Task::query()->create([
            'description' => $request->description,
            'user_id' => $request->user_id,
        ]);

        $task->refresh();

        Kafka::publishOn(topic: 'tasks-stream')
            ->withMessage(new Message(
                body: [
                    'event_name' => 'TaskCreated',
                    'public_id' => $task->public_id,
                    'data' => [
                        'description' => $task->description,
                        'status' => $task->status,
                        'public_user_id' => $task->user->public_id,
                    ]
                ]
            ))
            ->withDebugEnabled()
            ->send();

        Kafka::publishOn(topic: 'tasks-flow')
            ->withMessage(new Message(
                body: [
                    'event_name' => 'TaskAssigned',
                    'public_id' => $task->public_id,
                    'data' => [
                        'public_user_id' => $task->user->public_id,
                    ]
                ]
            ))
            ->withDebugEnabled()
            ->send();

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

        Kafka::publishOn(topic: 'tasks-flow')
            ->withMessage(new Message(
                body: [
                    'event_name' => 'TaskCompleted',
                    'public_id' => $task->public_id,
                    'data' => [
                        'public_user_id' => $task->user->public_id,
                    ]
                ]
            ))
            ->withDebugEnabled()
            ->send();

        return Redirect::to('/dashboard');
    }

    public function shuffle(Request $request)
    {
        if ($request->user()->role !== UserRole::manager) {
            throw new Exception(
                sprintf('Только пользователь с ролью %s можно переназначить задачи', UserRole::manager->value)
            );
        }

        $users = User::query()
            ->whereNotNull('name')
            ->whereNotNull('email')
            ->where('role', UserRole::worker->value)
            ->where('active', true)
            ->get();

        $tasks = Task::query()
            ->where('status', TaskStatus::processing)
            ->get();

        if (count($tasks) > 1 && count($users) > 1) {
            $messageBatch = new MessageBatch();

            /** @var Task $task */
            foreach ($tasks as $task) {
                do {
                    $newUserId = $users->random()->id;
                } while ($task->user_id === $newUserId);

                $task->user_id = $newUserId;
                $task->save();

                $messageBatch->push(
                    new Message(
                        body: [
                            'event_name' => 'TaskAssigned',
                            'public_id' => $task->public_id,
                            'data' => [
                                'public_user_id' => $task->user->public_id,
                            ]
                        ]
                    )
                );
            }

            Kafka::publishOn(topic: 'tasks-flow')
                ->withDebugEnabled()
                ->sendBatch($messageBatch);
        }

        return Redirect::to('/dashboard');
    }
}
