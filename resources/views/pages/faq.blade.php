<x-public-layout title="FAQ | Guide My Journey">
    @php
        $sections = [
            'Basics' => [
                [
                    'What is Guide My Journey?',
                    'Guide My Journey is a community suggestion board for creators. Fans suggest ideas, communities vote, and creators decide what to make, cover, or explore next.',
                ],
                [
                    'Is this only for reaction channels?',
                    'No. Guide My Journey can work for any creator. Fans can suggest YouTube links, topics, questions, tutorials, deep dives, reviews, or original ideas. Reaction channels are one use case, but the platform is built for creators broadly.',
                ],
                [
                    'How does Guide My Journey work?',
                    'Choose a creator, suggest an idea or link, and vote on the suggestions you want most. The creator gets an organized board that shows what their community is asking for, while still keeping full control over what they make.',
                ],
            ],
            'For Fans and Guides' => [
                [
                    'What is a Guide?',
                    'A Guide is a fan who helps shape a creator\'s journey. Guides can favorite creators, submit suggestions, and upvote active ideas they want to see next.',
                ],
                [
                    'What are resources?',
                    'Resources are the limits that help keep participation meaningful. Your resources include how many creators you can favorite, how many suggestions you can submit, and how many active upvotes you can use for each creator.',
                ],
                [
                    'Why do I have a limited number of upvotes?',
                    'Limited upvotes make each vote more meaningful. Instead of every idea getting a casual like, Guides choose the suggestions they care about most.',
                ],
                [
                    'Why do I need to favorite a creator before suggesting or upvoting?',
                    'Favoriting a creator means you want to participate in that creator\'s journey. Once you favorite a creator, you can use your suggestion and upvote resources on that creator\'s page.',
                ],
                [
                    'What happens if I unfavorite a creator?',
                    'If you unfavorite a creator, your active upvotes on that creator\'s suggestions are removed. This frees that creator slot so you can favorite someone else.',
                ],
                [
                    'Do votes guarantee a creator will make something?',
                    'No. Votes help creators understand community interest, but creators always decide what they make, cover, or explore.',
                ],
            ],
            'Suggestions and Voting' => [
                [
                    'What can I suggest?',
                    'You can suggest ideas, topics, questions, YouTube videos, source links, tutorials, reviews, deep dives, or anything that fits the creator\'s page and submission guidelines.',
                ],
                [
                    'What is the difference between a suggestion and an upvote?',
                    'A suggestion is a new idea added to a creator\'s board. An upvote is your way of supporting an existing suggestion so the creator can see what the community wants most.',
                ],
                [
                    'Can I upvote my own suggestion?',
                    'Yes. If you suggest something you care about, you can also use one of your upvotes on it.',
                ],
                [
                    'What happens when a creator marks a suggestion as Scheduled, Published, Passed, Hidden, or Already Seen?',
                    'When a suggestion is no longer actively being considered, its upvotes are released back to users. This lets Guides use those upvotes on other active suggestions.',
                ],
                [
                    'What does Already Seen mean?',
                    'Already Seen means the creator has already seen the video, topic, or idea before. It does not necessarily mean the creator dislikes the suggestion.',
                ],
            ],
            'For Creators' => [
                [
                    'How do creators create a page?',
                    'During beta, creators can manually set up a creator page from My Hub. You can add your channel name, YouTube channel URL, bio, submission instructions, avatar, hero image, and approval settings. YouTube verification is planned for later.',
                ],
                [
                    'Can creators approve suggestions before they appear publicly?',
                    'Yes. Creators can choose to hold new suggestions for review or auto-approve them. Hold for review keeps new suggestions private until approved. Auto-approve lets suggestions appear publicly right away.',
                ],
                [
                    'Can creators add their own starter suggestions?',
                    'Yes. Creators can seed their journey with starter suggestions so the page is not empty and the community has ideas to vote on right away.',
                ],
                [
                    'Can creators organize suggestions?',
                    'Yes. Creators can use statuses, categories, and creator-specific tags to organize suggestions for their community.',
                ],
                [
                    'Can creators remove inappropriate suggestions?',
                    'Yes. Creator page owners can hide or remove inappropriate suggestions and manage the status of submissions from their creator dashboard.',
                ],
                [
                    'Can creators block users?',
                    'Creator-level blocking is planned. For now, creators can hide or remove inappropriate suggestions from their dashboard.',
                ],
                [
                    'Do creators have to follow the vote results?',
                    'No. Votes provide signal, not obligation. Guide My Journey helps creators see what their community wants, but creators always keep creative control.',
                ],
            ],
            'Platform and YouTube' => [
                [
                    'Is Guide My Journey connected to YouTube?',
                    'Guide My Journey can display YouTube links and video details, but it is not affiliated with, endorsed by, or operated by YouTube.',
                ],
                [
                    'Do creators need YouTube verification?',
                    'During beta, creator pages are created manually. YouTube-based verification is planned for a later phase.',
                ],
                [
                    'Can suggestions be something other than a YouTube link?',
                    'Yes. Fans can suggest topics, questions, ideas, or links, as well as YouTube videos.',
                ],
                [
                    'Is Guide My Journey free?',
                    'The core product is free first. Fans can favorite creators, submit suggestions, and upvote ideas within their available resources. Paid features may come later, but the free creator and fan experience comes first.',
                ],
            ],
        ];
    @endphp

    <section class="px-4 py-14 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-4xl">
            <div class="max-w-3xl">
                <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-indigo-600 dark:text-indigo-400 sm:text-sm">FAQ</p>
                <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-5xl">
                    Frequently asked questions
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300 sm:text-lg sm:leading-8">
                    Learn how suggestions, upvotes, creator pages, and community resources work across Guide My Journey.
                </p>
            </div>

            <div class="mt-12 space-y-12">
                @foreach ($sections as $section => $faqs)
                    <section aria-labelledby="faq-{{ Str::slug($section) }}">
                        <div class="flex items-center gap-3">
                            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>
                            <h2 id="faq-{{ Str::slug($section) }}" class="shrink-0 text-xs font-extrabold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                {{ $section }}
                            </h2>
                            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></span>
                        </div>

                        <div class="mt-5 space-y-3">
                            @foreach ($faqs as [$question, $answer])
                                <details class="group rounded-2xl border border-slate-200 bg-white shadow-sm transition open:border-indigo-200 open:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:open:border-indigo-800">
                                    <summary class="cursor-pointer list-none rounded-2xl px-5 py-5 font-bold text-slate-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:text-white dark:focus-visible:ring-offset-slate-950 sm:px-6">
                                        <span class="flex items-start justify-between gap-4">
                                            <span>{{ $question }}</span>
                                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-lg leading-none text-indigo-600 transition group-open:rotate-45 dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">+</span>
                                        </span>
                                    </summary>
                                    <div class="border-t border-slate-100 px-5 py-5 dark:border-slate-800 sm:px-6">
                                        <p class="leading-7 text-slate-600 dark:text-slate-300">{{ $answer }}</p>
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </section>
</x-public-layout>
