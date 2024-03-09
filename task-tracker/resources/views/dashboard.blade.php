<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4">
                    <x-secondary-button onclick="location.href = '/task';">
                        {{ __('Add') }}
                    </x-secondary-button>
                    @if($currentUser->role === \App\Enums\UserRole::manager || $currentUser->role === \App\Enums\UserRole::admin)
                        <x-danger-button onclick="location.href = '/task/shuffle';">
                            {{ __('Заассайнить задачи') }}
                        </x-danger-button>
                    @endif
                </div>
                <div class="p-6 text-gray-900">
                    <table style="width: 100%; border: 1px; text-align: left" >
                        <tr>
                            <th>ID</th>
                            <th>Описание</th>
                            <th>Пользователь</th>
                            <th>Статус</th>
                            <th></th>
                        </tr>
                        @foreach($tasks as $task)
                            <tr>
                                <td>{{$task->id}}</td>
                                <td>{{$task->description}}</td>
                                <td>{{$task->user->name}} ({{$task->user->email}})</td>
                                <td>{{$task->status}}</td>
                                <td>
                                    @if($currentUser->role === \App\Enums\UserRole::worker && $currentUser->id === $task->user_id && $task->status !== \App\Enums\TaskStatus::done)
                                        <form method="post" action="{{ route('task.done', $task->id) }}">
                                            @csrf
                                            @method('patch')

                                            <x-primary-button>
                                                {{ __('Done') }}
                                            </x-primary-button>
                                        </form>
                                    @endif
                                </td>
                            </tr>

                        @endforeach

                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
