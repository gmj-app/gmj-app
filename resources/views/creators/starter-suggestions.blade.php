<x-app-layout>
    <x-slot name="title">Seed Your Journey</x-slot>

    <x-slot name="header">
        @include('creators.partials.header', ['section' => 'Seed Your Journey'])
    </x-slot>

    <div class="py-10 sm:py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @include('creators.partials.navigation')

            <section class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 p-6 dark:border-slate-800 sm:p-8">
                    <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Creator onboarding</p>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-4xl">Seed your journey</h1>
                    <p class="mt-3 text-lg font-semibold leading-7 text-slate-700 dark:text-slate-200">
                        Add up to 20 starter suggestions so your community has something to vote on right away.
                    </p>
                    <p class="mt-2 max-w-3xl leading-7 text-slate-600 dark:text-slate-300">
                        These can be video ideas, topics, questions, YouTube links, source links, or anything you want your community to help prioritize.
                    </p>
                    <p class="mt-3 text-sm font-medium text-slate-500 dark:text-slate-400">
                        Not ready? You can skip this and add suggestions later from your creator dashboard.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('creators.starter-suggestions.store', $creator) }}"
                    class="p-6 sm:p-8"
                    x-data="{ visibleRows: {{ max(3, min(20, count(old('suggestions', [])))) }} }"
                >
                    @csrf

                    @if ($errors->any())
                        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                            Please fix the highlighted starter suggestions and try again.
                        </div>
                    @endif

                    <div class="space-y-4">
                        @for ($index = 0; $index < 20; $index++)
                            <fieldset
                                x-show="visibleRows > {{ $index }}"
                                x-cloak
                                class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-950/40 sm:p-5"
                            >
                                <legend class="px-2 text-sm font-bold text-slate-500 dark:text-slate-400">Suggestion {{ $index + 1 }}</legend>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="md:col-span-2">
                                        <x-input-label :for="'suggestion-title-'.$index" value="Title" />
                                        <x-text-input
                                            :id="'suggestion-title-'.$index"
                                            :name="'suggestions['.$index.'][title]'"
                                            class="mt-1 block w-full"
                                            :value="old('suggestions.'.$index.'.title')"
                                            placeholder="Explain why bridges fail"
                                            maxlength="255"
                                        />
                                        <x-input-error :messages="$errors->get('suggestions.'.$index.'.title')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label :for="'suggestion-url-'.$index" value="Optional link" />
                                        <x-text-input
                                            :id="'suggestion-url-'.$index"
                                            :name="'suggestions['.$index.'][url]'"
                                            type="url"
                                            class="mt-1 block w-full"
                                            :value="old('suggestions.'.$index.'.url')"
                                            placeholder="Optional YouTube or source link"
                                            maxlength="500"
                                        />
                                        <x-input-error :messages="$errors->get('suggestions.'.$index.'.url')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label :for="'suggestion-category-'.$index" value="Category" />
                                        <select
                                            id="suggestion-category-{{ $index }}"
                                            name="suggestions[{{ $index }}][category]"
                                            class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                        >
                                            <option value="">No category</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category }}" @selected(old('suggestions.'.$index.'.category') === $category)>{{ ucfirst($category) }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('suggestions.'.$index.'.category')" class="mt-2" />
                                    </div>

                                    <div class="md:col-span-2">
                                        <x-input-label :for="'suggestion-note-'.$index" value="Note or context" />
                                        <textarea
                                            id="suggestion-note-{{ $index }}"
                                            name="suggestions[{{ $index }}][note]"
                                            rows="2"
                                            maxlength="1000"
                                            placeholder="Why should your community vote for this?"
                                            class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                                        >{{ old('suggestions.'.$index.'.note') }}</textarea>
                                        <x-input-error :messages="$errors->get('suggestions.'.$index.'.note')" class="mt-2" />
                                    </div>
                                </div>
                            </fieldset>
                        @endfor
                    </div>

                    <button
                        type="button"
                        x-show="visibleRows < 20"
                        @click="visibleRows++"
                        class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-200 bg-white px-4 py-2 text-sm font-bold text-indigo-700 hover:bg-indigo-50 dark:border-indigo-800 dark:bg-slate-900 dark:text-indigo-300 dark:hover:bg-indigo-950/50"
                    >
                        Add another suggestion
                    </button>

                    <div class="mt-8 flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-end dark:border-slate-800">
                        <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900">
                            Save starter suggestions
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('creators.starter-suggestions.skip', $creator) }}" class="px-6 pb-6 sm:px-8 sm:pb-8">
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200 sm:w-auto">
                        Skip for now
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
