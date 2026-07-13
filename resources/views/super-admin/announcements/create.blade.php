<x-super-admin-layout title="Create announcement">
    <div class="mb-6"><h2 class="text-2xl font-extrabold">Create announcement</h2><p class="mt-1 text-sm text-slate-500">Publish an in-app update to all users or active creator owners.</p></div>
    <form method="POST" action="{{ route('super-admin.announcements.store') }}">@csrf @include('super-admin.announcements._form', ['submitLabel' => 'Save announcement'])</form>
</x-super-admin-layout>
