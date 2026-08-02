<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\SubjectRequest;
use App\Models\CreatorChannel;
use App\Models\Subject;
use App\Services\CreatorIntelligence\Metadata\NameNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $r): View
    {
        $q = Subject::with('creatorChannel')->withCount(['videos', 'videos as primary_videos_count' => fn ($x) => $x->where('creator_video_subject.is_primary', true), 'contentItems']);
        if ($r->filled('q')) {
            $q->where(fn ($x) => $x->where('name', 'like', '%'.$r->q.'%')->orWhere('normalized_name', 'like', '%'.$r->q.'%')->orWhere('slug', 'like', '%'.$r->q.'%')->orWhere('subject_type', 'like', '%'.$r->q.'%')->orWhere('notes', 'like', '%'.$r->q.'%'));
        }if ($r->filled('creator_channel_id')) {
            $q->where('creator_channel_id', $r->integer('creator_channel_id'));
        }if (in_array($r->active, ['1', '0'], true)) {
            $q->where('is_active', $r->active === '1');
        }

        return view('super-admin.creator-intelligence.subjects.index', ['subjects' => $q->orderBy('name')->paginate(25)->withQueryString(), 'channels' => CreatorChannel::orderBy('channel_name')->get()]);
    }

    public function create(): View
    {
        return view('super-admin.creator-intelligence.subjects.form', ['subject' => new Subject, 'channels' => CreatorChannel::orderBy('channel_name')->get()]);
    }

    public function store(SubjectRequest $r, NameNormalizer $n): RedirectResponse
    {
        $subject = Subject::create($this->data($r, $n));

        return redirect()->route('superadmin.creator-intelligence.subjects.show', $subject)->with('success', 'Subject created.');
    }

    public function show(Subject $subject): View
    {
        return view('super-admin.creator-intelligence.subjects.show', ['subject' => $subject->loadCount(['videos', 'contentItems'])->load(['creatorChannel', 'contentItems']), 'videos' => $subject->videos()->with('channel')->latest('published_at')->paginate(25)]);
    }

    public function edit(Subject $subject): View
    {
        return view('super-admin.creator-intelligence.subjects.form', ['subject' => $subject, 'channels' => CreatorChannel::orderBy('channel_name')->get()]);
    }

    public function update(SubjectRequest $r, Subject $subject, NameNormalizer $n): RedirectResponse
    {
        $subject->update($this->data($r, $n, $subject));

        return redirect()->route('superadmin.creator-intelligence.subjects.show', $subject)->with('success', 'Subject updated.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return redirect()->route('superadmin.creator-intelligence.subjects.index')->with('success', 'Subject deleted; video links were detached and related content items retained.');
    }

    private function data(SubjectRequest $r, NameNormalizer $n, ?Subject $subject = null): array
    {
        $data = $r->validated();
        $data['normalized_name'] = $n->normalize($data['name']);
        $data['slug'] = $n->slug($data['name']);
        $exists = Subject::where('creator_channel_id', $data['creator_channel_id'])->where(fn ($q) => $q->where('normalized_name', $data['normalized_name'])->orWhere('slug', $data['slug']))->when($subject, fn ($q) => $q->whereKeyNot($subject->id))->exists();
        if ($exists) {
            throw ValidationException::withMessages(['name' => 'A subject with this normalized name already exists for the channel.']);
        }

        return $data;
    }
}
