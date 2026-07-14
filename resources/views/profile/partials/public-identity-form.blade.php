@props([
    'user',
    'action',
    'method' => 'patch',
    'submitLabel' => 'Save',
    'showAccountContext' => false,
])

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-slate-50">
            {{ __('Public guide identity') }}
        </h2>

        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-slate-300">
            {{ __('This is how other people will see you when you request, vote, or support requests.') }}
        </p>

        @if ($showAccountContext)
            <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:bg-slate-950/60 dark:text-slate-300">
                <p><span class="font-semibold">Account email:</span> {{ $user->email }}</p>
                <p class="mt-1"><span class="font-semibold">Google account name:</span> {{ $user->name }}</p>
            </div>
        @endif
    </header>

    <form method="post" action="{{ $action }}" class="mt-6 space-y-6">
        @csrf
        @if (strtolower($method) !== 'post')
            @method($method)
        @endif

        <div>
            <x-input-label for="public_display_name" :value="__('Display name')" />
            <x-text-input
                id="public_display_name"
                name="public_display_name"
                type="text"
                class="mt-1 block w-full"
                :value="old('public_display_name', $user->public_display_name)"
                required
                autofocus
                maxlength="40"
                autocomplete="nickname"
                placeholder="e.g. Jay, JFragment Fan, Cher Ree"
            />
            <x-input-error class="mt-2" :messages="$errors->get('public_display_name')" />
        </div>

        <div>
            <x-input-label for="public_handle" :value="__('Guide handle')" />
            <div class="mt-1 flex rounded-md shadow-sm">
                <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm font-semibold text-gray-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">@</span>
                <x-text-input
                    id="public_handle"
                    name="public_handle"
                    type="text"
                    class="block w-full rounded-l-none"
                    :value="old('public_handle', $user->public_handle)"
                    required
                    maxlength="30"
                    autocomplete="username"
                    placeholder="jfragmentfan"
                />
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-slate-400">
                {{ __('Use 3 to 30 letters, numbers, underscores, or hyphens. Do not include spaces.') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('public_handle')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ $submitLabel }}</x-primary-button>

            @if (session('status') === 'public-identity-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-slate-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
