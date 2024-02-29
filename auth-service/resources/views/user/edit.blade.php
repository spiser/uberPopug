<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            @if($user === null)
                {{ __('New User') }}
            @else
                {{ __('Edit User') }}
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
                                {{ __('User Information') }}
                            </h2>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ __("Update your account's user information and email address.") }}
                            </p>
                        </header>

                        <form method="post" action="@if($user === null) {{ route('user.store') }} @else {{ route('user.update', $user->id) }} @endif" class="mt-6 space-y-6">
                            @csrf
                            @if($user === null) @method('post') @else @method('patch') @endif

                            <div>
                                <x-input-label for="name" :value="__('Name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user?->name)" required autofocus autocomplete="name" />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user?->email)" required autocomplete="username" />
                                <x-input-error class="mt-2" :messages="$errors->get('email')" />
                            </div>

                            @if($user == null)
                                <div>
                                    <x-input-label for="password" :value="__('Password')" />
                                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="username" />
                                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
                                </div>
                            @endif

                            <div>
                                <x-input-label for="role" :value="__('Role')" />
                                <label class="mt-1 block" required autocomplete="username">
                                    <select name="role">
                                        @foreach(\App\Enums\UserRole::cases() as $userRole)
                                            <option {{ $userRole === $user?->role ? 'selected="selected"' : '' }} value="{{$userRole->value}}">{{$userRole->value}}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <x-input-error class="mt-2" :messages="$errors->get('role')" />
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
