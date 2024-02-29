<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class UserController extends Controller
{
    public function list(Request $request): View
    {
        $users = User::query()
            ->orderBy('id')
            ->get();

        return view('dashboard', [
            'users' => $users,
        ]);
    }

    public function edit(Request $request): View
    {
        $user = null;

        if ($request->id) {
            $user = User::query()->findOrFail($request->id);
        }

        return view('user.edit', [
            'user' => $user,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = User::query()->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $user->refresh();

        Kafka::publishOn(topic: 'users-stream')
            ->withMessage(new Message(
                body: [
                    'event_name' => 'UserCreated',
                    'public_id' => $user->public_id,
                    'data' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ]
                ]
            ))
            ->withDebugEnabled()
            ->send();


        Kafka::publishOn(topic: 'users-role')
            ->withMessage(new Message(
                body: [
                    'event_name' => 'UserRoleChanged',
                    'public_id' => $user->public_id,
                    'data' => [
                        'role' => $user->role,
                    ]
                ]
            ))
            ->withDebugEnabled()
            ->send();

        return Redirect::to('/dashboard');
    }

    public function update(FormRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = User::query()->findOrFail($request->id);

        $user->fill([
            'name' => $request->name,
            'email' => $request->email,
            'role' => UserRole::from($request->role),
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Kafka::publishOn(topic: 'users-stream')
            ->withMessage(new Message(
                body: [
                    'event_name' => 'UserUpdated',
                    'public_id' => $user->public_id,
                    'data' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ]
                ]
            ))
            ->withDebugEnabled()
            ->send();

        if ($request->get('role', false)) {
            Kafka::publishOn(topic: 'users-role')
                ->withMessage(new Message(
                    body: [
                        'event_name' => 'UserRoleChanged',
                        'public_id' => $user->public_id,
                        'data' => [
                            'role' => $user->role,
                        ]
                    ]
                ))
                ->withDebugEnabled()
                ->send();
        }

        return Redirect::to('/dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($request->id === $request->user()->id) {
            throw new \Exception('Нельзя удалить самого себя');
        }

        $user = User::query()->findOrFail($request->id);

        $user->active = false;
        $user->save();

        Kafka::publishOn(topic: 'users-stream')
            ->withMessage(new Message(
                body: [
                    'event_name' => 'UserDeleted',
                    'public_id' => $user->public_id
                ]
            ))
            ->withDebugEnabled()
            ->send();

        return Redirect::to('/dashboard');
    }
}
