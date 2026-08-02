<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\SubjectRequest;
use App\Models\CreatorChannel;
use App\Models\Subject;
use App\Services\CreatorIntelligence\Metadata\NameNormalizer;
use App\Services\SuperAdminAuditService;
use App\Support\CreatorIntelligenceLabels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __construct(private readonly SuperAdminAuditService $audit) {}

    public function index(Request $r, NameNormalizer $normalizer): View
    {
        $q = Subject::with('creatorChannel')->withCount(['videos', 'videos as primary_videos_count' => fn ($x) => $x->where('creator_video_subject.is_primary', true), 'contentItems']);
        if ($r->filled('q')) {
            $search = $normalizer->display($r->string('q')->toString());
            $normalizedSearch = $normalizer->normalize($search);
            $q->where(fn ($x) => $x->where('name', 'like', '%'.$search.'%')->orWhere('normalized_name', 'like', '%'.$normalizedSearch.'%')->orWhere('slug', 'like', '%'.$normalizedSearch.'%')->orWhere('subject_type', 'like', '%'.$search.'%')->orWhere('notes', 'like', '%'.$search.'%'));
        }if ($r->filled('creator_channel_id')) {
            $q->where('creator_channel_id', $r->integer('creator_channel_id'));
        }if (in_array($r->active, ['1', '0'], true)) {
            $q->where('is_active', $r->active === '1');
        }

        $selectedChannel = $r->filled('creator_channel_id') ? CreatorChannel::find($r->integer('creator_channel_id')) : null;

        return view('super-admin.creator-intelligence.subjects.index', ['subjects' => $q->orderBy('name')->paginate(25)->withQueryString(), 'channels' => CreatorChannel::orderBy('channel_name')->get(), 'labels' => CreatorIntelligenceLabels::for($selectedChannel), 'selectedChannel' => $selectedChannel]);
    }

    public function create(Request $request): View
    {
        $selectedChannel = $request->filled('creator_channel_id') ? CreatorChannel::find($request->integer('creator_channel_id')) : null;

        return view('super-admin.creator-intelligence.subjects.form', ['subject' => new Subject(['creator_channel_id' => $selectedChannel?->id]), 'channels' => CreatorChannel::orderBy('channel_name')->get(), 'labels' => CreatorIntelligenceLabels::for($selectedChannel)]);
    }

    public function store(SubjectRequest $r, NameNormalizer $n): RedirectResponse
    {
        $subject = Subject::create($this->data($r, $n));
        $this->audit->record($r->user(), $subject, 'creator_intelligence.subject.created', 'Creator Intelligence subject created.', [], $subject->only(['name', 'normalized_name']), ['submitted_name' => $r->string('name')->toString()], $r);

        return redirect()->route('superadmin.creator-intelligence.subjects.show', $subject)->with('success', CreatorIntelligenceLabels::for($subject->creatorChannel)->subject.' created.');
    }

    public function show(Subject $subject): View
    {
        return view('super-admin.creator-intelligence.subjects.show', ['subject' => $subject->loadCount(['videos', 'contentItems'])->load(['creatorChannel', 'contentItems']), 'videos' => $subject->videos()->with('channel')->latest('published_at')->paginate(25), 'labels' => CreatorIntelligenceLabels::for($subject->creatorChannel)]);
    }

    public function edit(Subject $subject): View
    {
        return view('super-admin.creator-intelligence.subjects.form', ['subject' => $subject, 'channels' => CreatorChannel::orderBy('channel_name')->get(), 'labels' => CreatorIntelligenceLabels::for($subject->creatorChannel)]);
    }

    public function update(SubjectRequest $r, Subject $subject, NameNormalizer $n): RedirectResponse
    {
        $before = $subject->only(['name', 'normalized_name']);
        $subject->update($this->data($r, $n, $subject));
        $this->audit->record($r->user(), $subject, 'creator_intelligence.subject.updated', 'Creator Intelligence subject updated.', $before, $subject->only(['name', 'normalized_name']), ['submitted_name' => $r->string('name')->toString()], $r);

        return redirect()->route('superadmin.creator-intelligence.subjects.show', $subject)->with('success', CreatorIntelligenceLabels::for($subject->creatorChannel)->subject.' updated.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $channelId = $subject->creator_channel_id;
        $labels = CreatorIntelligenceLabels::for($subject->creatorChannel);
        $subject->delete();

        return redirect()->route('superadmin.creator-intelligence.subjects.index', ['creator_channel_id' => $channelId])->with('success', "{$labels->subject} deleted; video links were detached and related {$labels->lowerContentItems()} retained.");
    }

    private function data(SubjectRequest $r, NameNormalizer $n, ?Subject $subject = null): array
    {
        $data = $r->validated();
        $data['name'] = $n->display($data['name']);
        $data['normalized_name'] = $n->normalize($data['name']);
        $data['slug'] = $n->slug($data['name']);
        $exists = Subject::where('creator_channel_id', $data['creator_channel_id'])->where(fn ($q) => $q->where('normalized_name', $data['normalized_name'])->orWhere('slug', $data['slug']))->when($subject, fn ($q) => $q->whereKeyNot($subject->id))->exists();
        if ($exists) {
            $label = CreatorIntelligenceLabels::for(CreatorChannel::find($data['creator_channel_id']))->lowerSubject();
            throw ValidationException::withMessages(['name' => "This {$label} already exists for the selected creator channel."]);
        }

        return $data;
    }
}
