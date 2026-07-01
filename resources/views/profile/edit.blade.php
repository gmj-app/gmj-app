<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-50 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
        </div>
    </div>
</x-app-layout>
