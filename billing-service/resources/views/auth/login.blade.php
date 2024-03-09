<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <a href="{{ route('sso.login') }}" class="bnt bnt-block bnt-danger bnt-sm">Login with SSO</a>
</x-guest-layout>
