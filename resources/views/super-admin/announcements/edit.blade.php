<x-super-admin-layout title="Edit announcement">
    <div class="mb-6"><h2 class="text-2xl font-extrabold">Edit announcement</h2><p class="mt-1 text-sm text-slate-500">Recipient-facing fields lock after delivery is queued.</p></div>
    <form method="POST" action="{{ route('super-admin.announcements.update', $announcement) }}">@csrf @method('PATCH') @include('super-admin.announcements._form', ['submitLabel' => 'Update announcement'])</form>
</x-super-admin-layout>
