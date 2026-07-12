<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Services\CreatorTagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CreatorSetupController extends Controller
{
    public function __construct(
        private readonly CreatorTagService $tags,
    ) {}

    public function create(): View
    {
        return view('creators.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('creators', 'slug'),
            ],
            'youtube_channel_url' => ['required', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'submission_instructions' => ['nullable', 'string', 'max:2000'],
            'submissions_open' => ['required', 'boolean'],
        ], [
            'slug.regex' => 'The page URL may only contain lowercase letters, numbers, and single hyphens.',
        ]);

        $creator = DB::transaction(function () use ($request, $validated): Creator {
            $creator = Creator::query()->create([
                ...$validated,
                'channel_url' => $validated['youtube_channel_url'],
                'submissions_open' => $request->boolean('submissions_open'),
                'recommendation_approval_mode' => Creator::APPROVAL_MODE_AUTO,
                'verification_status' => 'unverified',
                'status' => 'active',
            ]);

            $creator->creatorOwners()->create([
                'user_id' => $request->user()->id,
                'role' => 'owner',
            ]);

            $this->tags->createDefaults($creator);

            return $creator;
        });

        return redirect()
            ->route('creators.starter-suggestions.create', $creator);
    }
}
