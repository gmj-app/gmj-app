@props(['creator'])

<section aria-labelledby="creator-branding-heading">
    <div>
        <h3 id="creator-branding-heading" class="text-lg font-semibold text-gray-900 dark:text-slate-50">Branding</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">Choose a new image to replace the current one. Existing images remain unchanged until a replacement uploads successfully.</p>
    </div>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <div x-data="{ preview: null, select(event) { const file = event.target.files[0]; if (! file) return; if (this.preview) URL.revokeObjectURL(this.preview); this.preview = URL.createObjectURL(file); } }" class="rounded-2xl border border-gray-200 p-4 dark:border-slate-800">
            <x-input-label for="avatar" value="Avatar" />
            <div class="mt-3 flex items-center gap-4">
                <template x-if="preview"><img x-bind:src="preview" alt="New avatar preview" class="size-24 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700"></template>
                <div x-show="! preview"><x-creator-avatar :creator="$creator" size="lg" class="ring-1 ring-slate-200 dark:ring-slate-700" /></div>
                <p class="text-sm leading-6 text-gray-600 dark:text-slate-300">Recommended: square image, at least 512x512. JPG, PNG, or WebP. Max 2 MB.</p>
            </div>
            <input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" x-on:change="select($event)" class="mt-4 block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-slate-300 dark:file:bg-indigo-950 dark:file:text-indigo-300">
            <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
        </div>

        <div x-data="{ preview: null, select(event) { const file = event.target.files[0]; if (! file) return; if (this.preview) URL.revokeObjectURL(this.preview); this.preview = URL.createObjectURL(file); } }" class="rounded-2xl border border-gray-200 p-4 dark:border-slate-800">
            <x-input-label for="hero" value="Banner" />
            <template x-if="preview"><img x-bind:src="preview" alt="New banner preview" class="mt-3 h-28 w-full rounded-xl object-cover ring-1 ring-slate-200 dark:ring-slate-700"></template>
            <div x-show="! preview"><x-creator-hero-background :creator="$creator" class="mt-3 h-28 rounded-xl ring-1 ring-slate-200 dark:ring-slate-700" /></div>
            <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-slate-300">Recommended: wide image, around 1600x500 or larger. JPG, PNG, or WebP. Max 5 MB.</p>
            <input id="hero" name="hero" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" x-on:change="select($event)" class="mt-4 block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-slate-300 dark:file:bg-indigo-950 dark:file:text-indigo-300">
            <x-input-error :messages="$errors->get('hero')" class="mt-2" />
        </div>
    </div>
</section>
