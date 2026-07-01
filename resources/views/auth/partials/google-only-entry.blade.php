<div class="text-center">
    <h1 class="text-2xl font-extrabold text-gray-900 dark:text-slate-50">
        Sign in
    </h1>
    <p class="mx-auto mt-2 max-w-sm text-base font-semibold leading-6 text-gray-800 dark:text-slate-100">
        <x-brand-tagline />
    </p>
</div>

<div class="mt-5">
    <a
        href="{{ route('auth.google.redirect') }}"
        class="inline-flex min-h-12 w-full items-center justify-center gap-3 rounded-full border border-slate-300 bg-white px-5 py-3 text-base font-bold text-slate-800 shadow-sm transition hover:border-indigo-300 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:hover:border-indigo-500 dark:hover:bg-slate-900 dark:focus-visible:ring-offset-slate-900"
    >
        <span class="flex size-6 items-center justify-center rounded-full bg-white text-lg font-extrabold text-slate-700">G</span>
        Continue with Google
    </a>
</div>
