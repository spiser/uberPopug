<?php

namespace App\Http\Controllers;

use App\Services\KafkaService;
use App\Enums\UserRole;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Ramsey\Uuid\Uuid;

class UserController extends Controller
{
    public function __construct(
        private readonly KafkaService $kafkaService
    ) {
    }

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
        DB::beginTransaction();

        try {
            /** @var User $user */
            $user = User::query()->create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            $user->refresh();

            $this->kafkaService->produce(
                topic: 'users-stream',
                data: [
                    'event_id' => Uuid::uuid6()->toString(),
                    'event_version' => '1',
                    'event_name' => 'UserCreated',
                    'event_time' => time(),
                    'producer' => env('APP_NAME'),
                    'data' => [
                        'public_id' => $user->public_id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ]
                ]
            );

            $this->kafkaService->produce(
                topic: 'users-role',
                data: [
                    'event_id' => Uuid::uuid6()->toString(),
                    'event_version' => '1',
                    'event_name' => 'UserRoleChanged',
                    'event_time' => time(),
                    'producer' => env('APP_NAME'),
                    'data' => [
                        'public_id' => $user->public_id,
                        'role' => $user->role->value,
                    ]
                ]
            );

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

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

        $this->kafkaService->produce(
            topic: 'users-stream',
            data: [
                'event_id' => Uuid::uuid6()->toString(),
                'event_version' => '1',
                'event_name' => 'UserUpdated',
                'event_time' => time(),
                'producer' => env('APP_NAME'),
                'data' => [
                    'public_id' => $user->public_id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ]
        );

        if ($request->get('role', false)) {
            $this->kafkaService->produce(
                topic: 'users-role',
                data: [
                    'event_id' => Uuid::uuid6()->toString(),
                    'event_version' => '1',
                    'event_name' => 'UserRoleChanged',
                    'event_time' => time(),
                    'producer' => env('APP_NAME'),
                    'data' => [
                        'public_id' => $user->public_id,
                        'role' => $user->role->value,
                    ]
                ]
            );
        }

        return Redirect::to('/dashboard');
    }
}
