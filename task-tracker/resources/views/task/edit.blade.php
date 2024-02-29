<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            @if($task === null)
                {{ __('New Task') }}
            @else
                {{ __('Edit Task') }}
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Task Information') }}
                            </h2>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ __("Update task information.") }}
                            </p>
                        </header>

                        <form method="post" action="@if($task === null) {{ route('task.store') }} @else {{ route('task.update', $task->id) }} @endif" class="mt-6 space-y-6">
                            @csrf
                            @if($task === null) @method('post') @else @method('patch') @endif

                            <div>
                                <x-input-label for="description" :value="__('Description')" />
                                <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $task?->description)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('description')" />
                            </div>

                            <div>
                                <x-input-label for="user_id" :value="__('User')" />
                                <label class="mt-1 block" required>
                                    <select name="user_id">
                                        @foreach($users as $user)
                                            <option {{ $user->id === $task?->user_id ? 'selected="selected"' : '' }} value="{{$user->id}}">{{$user->name}} ({{$user->email}})</option>
                                        @endforeach
                                    </select>
                                </label>
                                <x-input-error class="mt-2" :messages="$errors->get('user_id')" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('Save') }}</x-primary-button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
