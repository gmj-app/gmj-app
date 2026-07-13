@props([
    'recommendation',
    'compact' => false,
])

@can('updateOwnPresentation', $recommendation)
    <a
        href="{{ route('requests.presentation.edit', $recommendation) }}"
        {{ $attributes->class('inline-flex min-h-10 items-center text-sm font-semibold text-indigo-600 transition hover:text-indigo-500 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300') }}
    >
        {{ $compact ? 'Edit details' : 'Edit request details' }}
    </a>
@endcan
