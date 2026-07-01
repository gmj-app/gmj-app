<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @include('auth.partials.google-only-entry')
</x-guest-layout>
