<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto min-w-0 max-w-5xl">
            <x-page-header eyebrow="Account" title="Profile" subtitle="Manage your public identity and account details." compact />
        </div>
    </x-slot>

    <div class="py-12">
        <main class="mx-auto min-w-0 max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8" data-profile-content>
            <div class="border border-gray-200 bg-white p-4 shadow dark:border-slate-800 dark:bg-slate-900 sm:rounded-lg sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.public-identity-form', [
                        'user' => $user,
                        'action' => route('profile.public-identity.update'),
                        'method' => 'patch',
                        'submitLabel' => 'Save public identity',
                        'showAccountContext' => true,
                    ])
                </div>
            </div>

            <div class="border border-gray-200 bg-white p-4 shadow dark:border-slate-800 dark:bg-slate-900 sm:rounded-lg sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="border border-gray-200 bg-white p-4 shadow dark:border-slate-800 dark:bg-slate-900 sm:rounded-lg sm:p-8">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-slate-50">
                        {{ __('Sign-in method') }}
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-slate-300">
                        {{ __('Your account uses Google sign-in. Password management is not part of the Guide My Journey MVP.') }}
                    </p>
                </div>
            </div>

            <div class="border border-gray-200 bg-white p-4 shadow dark:border-slate-800 dark:bg-slate-900 sm:rounded-lg sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </main>
    </div>
</x-app-layout>
