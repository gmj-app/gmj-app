<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\ContentItemRequest;
use App\Models\ContentItem;
use App\Models\CreatorChannel;
use App\Models\Subject;
use App\Services\CreatorIntelligence\Metadata\NameNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContentItemController extends Controller
{
    public function index(Request $r): View
    {
        $q = ContentItem::with(['creatorChannel', 'subject'])->withCount(['videos', 'videos as primary_videos_count' => fn ($x) => $x->where('creator_video_content_item.is_primary', true)]);
        if ($r->filled('q')) {
            $q->where(fn ($x) => $x->where('name', 'like', '%'.$r->q.'%')->orWhere('normalized_name', 'like', '%'.$r->q.'%')->orWhere('slug', 'like', '%'.$r->q.'%')->orWhere('content_item_type', 'like', '%'.$r->q.'%')->orWhereHas('subject', fn ($s) => $s->where('name', 'like', '%'.$r->q.'%')));
        }if ($r->filled('creator_channel_id')) {
            $q->where('creator_channel_id', $r->integer('creator_channel_id'));
        }if ($r->filled('subject_id')) {
            $q->where('subject_id', $r->integer('subject_id'));
        }

        return view('super-admin.creator-intelligence.content-items.index', ['items' => $q->orderBy('name')->paginate(25)->withQueryString(), 'channels' => CreatorChannel::orderBy('channel_name')->get()]);
    }

    public function create(): View
    {
        return view('super-admin.creator-intelligence.content-items.form', ['contentItem' => new ContentItem, 'channels' => CreatorChannel::orderBy('channel_name')->get(), 'subjects' => Subject::orderBy('name')->get()]);
    }

    public function store(ContentItemRequest $r, NameNormalizer $n): RedirectResponse
    {
        $item = ContentItem::create($this->data($r, $n));

        return redirect()->route('superadmin.creator-intelligence.content-items.show', $item)->with('success', 'Content item created.');
    }

    public function show(ContentItem $contentItem): View
    {
        return view('super-admin.creator-intelligence.content-items.show', ['item' => $contentItem->load(['creatorChannel', 'subject'])->loadCount('videos'), 'videos' => $contentItem->videos()->latest('published_at')->paginate(25)]);
    }

    public function edit(ContentItem $contentItem): View
    {
        return view('super-admin.creator-intelligence.content-items.form', ['contentItem' => $contentItem, 'channels' => CreatorChannel::orderBy('channel_name')->get(), 'subjects' => Subject::orderBy('name')->get()]);
    }

    public function update(ContentItemRequest $r, ContentItem $contentItem, NameNormalizer $n): RedirectResponse
    {
        $contentItem->update($this->data($r, $n, $contentItem));

        return redirect()->route('superadmin.creator-intelligence.content-items.show', $contentItem)->with('success', 'Content item updated.');
    }

    public function destroy(ContentItem $contentItem): RedirectResponse
    {
        $contentItem->delete();

        return redirect()->route('superadmin.creator-intelligence.content-items.index')->with('success', 'Content item deleted and detached from videos.');
    }

    private function data(ContentItemRequest $r, NameNormalizer $n, ?ContentItem $item = null): array
    {
        $data = $r->validated();
        $data['normalized_name'] = $n->normalize($data['name']);
        $data['slug'] = $n->slug($data['name']);
        $exists = ContentItem::where('creator_channel_id', $data['creator_channel_id'])->where(fn ($q) => $q->where('normalized_name', $data['normalized_name'])->orWhere('slug', $data['slug']))->when($item, fn ($q) => $q->whereKeyNot($item->id))->exists();
        if ($exists) {
            throw ValidationException::withMessages(['name' => 'A content item with this normalized name already exists for the channel.']);
        }

        return $data;
    }
}
