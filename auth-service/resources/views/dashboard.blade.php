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
                    <x-secondary-button onclick="location.href = '/user';">
                        {{ __('Add') }}
                    </x-secondary-button>
                </div>
                <div class="p-6 text-gray-900">
                    <table style="width: 100%; border: 1px; text-align: left" >
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>public ID</th>
                            <th>Активный?</th>
                            <th>Роль</th>
                            <th></th>
                        </tr>
                        @foreach($users as $user)
                            <tr>
                                <td>{{$user->id}}</td>
                                <td>{{$user->name}}</td>
                                <td>{{$user->email}}</td>
                                <td>{{$user->public_id}}</td>
                                <td>{{$user->active ? 'Да' : 'Нет'}}</td>
                                <td>{{$user->role}}</td>
                                <td>
                                    <x-secondary-button onclick="location.href = '/user/{{$user->id}}';">
                                        {{ __('Edit') }}
                                    </x-secondary-button>
                                </td>
                            </tr>

                        @endforeach

                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
