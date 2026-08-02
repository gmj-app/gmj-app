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

        $selectedChannel = $r->filled('creator_channel_id') ? CreatorChannel::find($r->integer('creator_channel_id')) : null;

        return view('super-admin.creator-intelligence.content-items.index', ['items' => $q->orderBy('name')->paginate(25)->withQueryString(), 'channels' => CreatorChannel::orderBy('channel_name')->get(), 'contentItemLabel' => $selectedChannel?->content_item_label ?? 'Content Item', 'subjectLabel' => $selectedChannel?->subject_label ?? 'Subject', 'selectedChannel' => $selectedChannel]);
    }

    public function create(Request $request): View
    {
        $selectedChannel = $request->filled('creator_channel_id') ? CreatorChannel::find($request->integer('creator_channel_id')) : null;

        return view('super-admin.creator-intelligence.content-items.form', ['contentItem' => new ContentItem(['creator_channel_id' => $selectedChannel?->id]), 'channels' => CreatorChannel::orderBy('channel_name')->get(), 'subjects' => Subject::orderBy('name')->get(), 'contentItemLabel' => $selectedChannel?->content_item_label ?? 'Content Item', 'subjectLabel' => $selectedChannel?->subject_label ?? 'Subject']);
    }

    public function store(ContentItemRequest $r, NameNormalizer $n): RedirectResponse
    {
        $item = ContentItem::create($this->data($r, $n));

        return redirect()->route('superadmin.creator-intelligence.content-items.show', $item)->with('success', $item->creatorChannel->content_item_label.' created.');
    }

    public function show(ContentItem $contentItem): View
    {
        return view('super-admin.creator-intelligence.content-items.show', ['item' => $contentItem->load(['creatorChannel', 'subject'])->loadCount('videos'), 'videos' => $contentItem->videos()->latest('published_at')->paginate(25), 'contentItemLabel' => $contentItem->creatorChannel->content_item_label, 'subjectLabel' => $contentItem->creatorChannel->subject_label]);
    }

    public function edit(ContentItem $contentItem): View
    {
        return view('super-admin.creator-intelligence.content-items.form', ['contentItem' => $contentItem, 'channels' => CreatorChannel::orderBy('channel_name')->get(), 'subjects' => Subject::orderBy('name')->get(), 'contentItemLabel' => $contentItem->creatorChannel->content_item_label, 'subjectLabel' => $contentItem->creatorChannel->subject_label]);
    }

    public function update(ContentItemRequest $r, ContentItem $contentItem, NameNormalizer $n): RedirectResponse
    {
        $contentItem->update($this->data($r, $n, $contentItem));

        return redirect()->route('superadmin.creator-intelligence.content-items.show', $contentItem)->with('success', $contentItem->creatorChannel->content_item_label.' updated.');
    }

    public function destroy(ContentItem $contentItem): RedirectResponse
    {
        $channelId = $contentItem->creator_channel_id;
        $label = $contentItem->creatorChannel->content_item_label;
        $contentItem->delete();

        return redirect()->route('superadmin.creator-intelligence.content-items.index', ['creator_channel_id' => $channelId])->with('success', "{$label} deleted and detached from videos.");
    }

    private function data(ContentItemRequest $r, NameNormalizer $n, ?ContentItem $item = null): array
    {
        $data = $r->validated();
        $data['normalized_name'] = $n->normalize($data['name']);
        $data['slug'] = $n->slug($data['name']);
        $exists = ContentItem::where('creator_channel_id', $data['creator_channel_id'])->where(fn ($q) => $q->where('normalized_name', $data['normalized_name'])->orWhere('slug', $data['slug']))->when($item, fn ($q) => $q->whereKeyNot($item->id))->exists();
        if ($exists) {
            $label = CreatorChannel::find($data['creator_channel_id'])?->content_item_label ?? 'Content Item';
            $article = in_array(strtolower(substr($label, 0, 1)), ['a', 'e', 'i', 'o', 'u'], true) ? 'An' : 'A';
            throw ValidationException::withMessages(['name' => "{$article} {$label} with this normalized name already exists for the channel."]);
        }

        return $data;
    }
}
