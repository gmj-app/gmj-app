@auth
    @php
        $user = auth()->user();
        $skipDisplayNamePrompt = request()->routeIs(
            'profile.*',
            'login',
            'register',
            'password.*',
            'verification.*',
            'internal.*',
            'tools.*',
        );
    @endphp

    @if (! $skipDisplayNamePrompt && ($user->shouldSeeDisplayNamePrompt() || $errors->displayNamePrompt->any()))
        <x-display-name-prompt-modal :user="$user" />
    @endif
@endauth
