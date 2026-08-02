@csrf
@if(isset($channel)) @method('PUT') @endif
<div class="grid gap-5 sm:grid-cols-2">
    <label class="font-bold">Creator profile<select name="creator_profile_id" required class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"><option value="">Select a profile</option>@foreach($profiles as $profile)<option value="{{ $profile->id }}" @selected((string) old('creator_profile_id', $channel->creator_profile_id ?? '') === (string) $profile->id)>{{ $profile->display_name }}</option>@endforeach</select></label>
    <label class="font-bold">Channel name<input name="channel_name" value="{{ old('channel_name', $channel->channel_name ?? '') }}" required class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></label>
    <label class="font-bold">Platform<input name="platform" value="{{ old('platform', $channel->platform ?? 'youtube') }}" required class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></label>
    <label class="font-bold">Platform channel ID<input name="platform_channel_id" value="{{ old('platform_channel_id', $channel->platform_channel_id ?? '') }}" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></label>
    <label class="font-bold">Handle<input name="handle" value="{{ old('handle', $channel->handle ?? '') }}" class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></label>
    <label class="font-bold">Publish timezone<input name="default_publish_timezone" value="{{ old('default_publish_timezone', $channel->default_publish_timezone ?? 'America/New_York') }}" required class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></label>
    <label class="font-bold">Subject label<input name="subject_label" value="{{ old('subject_label', $channel->subject_label ?? 'Subject') }}" required class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></label>
    <label class="font-bold">Content item label<input name="content_item_label" value="{{ old('content_item_label', $channel->content_item_label ?? 'Content Item') }}" required class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></label>
    <label class="font-bold">Category label<input name="category_label" value="{{ old('category_label', $channel->category_label ?? 'Category') }}" required class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></label>
    <label class="flex items-center gap-3 font-bold"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $channel->is_active ?? true)) class="rounded border-slate-300 text-indigo-600">Active</label>
</div>
@if($errors->any())<div class="mt-5 rounded-xl bg-rose-100 p-4 text-rose-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<button class="mt-6 rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white">Save channel</button>
