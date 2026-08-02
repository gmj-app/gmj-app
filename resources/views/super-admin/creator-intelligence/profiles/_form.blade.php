@csrf
@if(isset($profile)) @method('PUT') @endif
<div class="grid gap-5 sm:grid-cols-2">
    <label class="font-bold">Display name<input name="display_name" value="{{ old('display_name', $profile->display_name ?? '') }}" required class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></label>
    <label class="font-bold">Slug<input name="slug" value="{{ old('slug', $profile->slug ?? '') }}" required class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></label>
    <label class="font-bold">Timezone<input name="timezone" value="{{ old('timezone', $profile->timezone ?? 'America/New_York') }}" required class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></label>
    <label class="font-bold">Currency<input name="default_currency" maxlength="3" value="{{ old('default_currency', $profile->default_currency ?? 'USD') }}" required class="mt-2 w-full rounded-xl border-slate-300 uppercase dark:border-slate-700 dark:bg-slate-950"></label>
</div>
@if($errors->any())<div class="mt-5 rounded-xl bg-rose-100 p-4 text-rose-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<button class="mt-6 rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white">Save profile</button>
