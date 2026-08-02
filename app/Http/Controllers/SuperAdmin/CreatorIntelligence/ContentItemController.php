<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\ContentItemRequest;
use App\Models\ContentItem;
use App\Models\CreatorChannel;
use App\Models\Subject;
use App\Services\CreatorIntelligence\Metadata\NameNormalizer;
use App\Services\SuperAdminAuditService;
use App\Support\CreatorIntelligenceLabels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContentItemController extends Controller
{
    public function __construct(private readonly SuperAdminAuditService $audit) {}

    public function index(Request $r, NameNormalizer $normalizer): View
    {
        $q = ContentItem::with(['creatorChannel', 'subject'])->withCount(['videos', 'videos as primary_videos_count' => fn ($x) => $x->where('creator_video_content_item.is_primary', true)]);
        if ($r->filled('q')) {
            $search = $normalizer->display($r->string('q')->toString());
            $normalizedSearch = $normalizer->normalize($search);
            $q->where(fn ($x) => $x->where('name', 'like', '%'.$search.'%')->orWhere('normalized_name', 'like', '%'.$normalizedSearch.'%')->orWhere('slug', 'like', '%'.$normalizedSearch.'%')->orWhere('content_item_type', 'like', '%'.$search.'%')->orWhereHas('subject', fn ($s) => $s->where('normalized_name', 'like', '%'.$normalizedSearch.'%')));
        }if ($r->filled('creator_channel_id')) {
            $q->where('creator_channel_id', $r->integer('creator_channel_id'));
        }if ($r->filled('subject_id')) {
            $q->where('subject_id', $r->integer('subject_id'));
        }

        $selectedChannel = $r->filled('creator_channel_id') ? CreatorChannel::find($r->integer('creator_channel_id')) : null;

        return view('super-admin.creator-intelligence.content-items.index', ['items' => $q->orderBy('name')->paginate(25)->withQueryString(), 'channels' => CreatorChannel::orderBy('channel_name')->get(), 'labels' => CreatorIntelligenceLabels::for($selectedChannel), 'selectedChannel' => $selectedChannel]);
    }

    public function create(Request $request): View
    {
        $selectedChannel = $request->filled('creator_channel_id') ? CreatorChannel::find($request->integer('creator_channel_id')) : null;

        return view('super-admin.creator-intelligence.content-items.form', ['contentItem' => new ContentItem(['creator_channel_id' => $selectedChannel?->id]), 'channels' => CreatorChannel::orderBy('channel_name')->get(), 'subjects' => Subject::orderBy('name')->get(), 'labels' => CreatorIntelligenceLabels::for($selectedChannel)]);
    }

    public function store(ContentItemRequest $r, NameNormalizer $n): RedirectResponse
    {
        $item = ContentItem::create($this->data($r, $n));
        $this->audit->record($r->user(), $item, 'creator_intelligence.content_item.created', 'Creator Intelligence content item created.', [], $item->only(['name', 'normalized_name']), ['submitted_name' => $r->string('name')->toString()], $r);

        return redirect()->route('superadmin.creator-intelligence.content-items.show', $item)->with('success', CreatorIntelligenceLabels::for($item->creatorChannel)->contentItem.' created.');
    }

    public function show(ContentItem $contentItem): View
    {
        return view('super-admin.creator-intelligence.content-items.show', ['item' => $contentItem->load(['creatorChannel', 'subject'])->loadCount('videos'), 'videos' => $contentItem->videos()->latest('published_at')->paginate(25), 'labels' => CreatorIntelligenceLabels::for($contentItem->creatorChannel)]);
    }

    public function edit(ContentItem $contentItem): View
    {
        return view('super-admin.creator-intelligence.content-items.form', ['contentItem' => $contentItem, 'channels' => CreatorChannel::orderBy('channel_name')->get(), 'subjects' => Subject::orderBy('name')->get(), 'labels' => CreatorIntelligenceLabels::for($contentItem->creatorChannel)]);
    }

    public function update(ContentItemRequest $r, ContentItem $contentItem, NameNormalizer $n): RedirectResponse
    {
        $before = $contentItem->only(['name', 'normalized_name']);
        $contentItem->update($this->data($r, $n, $contentItem));
        $this->audit->record($r->user(), $contentItem, 'creator_intelligence.content_item.updated', 'Creator Intelligence content item updated.', $before, $contentItem->only(['name', 'normalized_name']), ['submitted_name' => $r->string('name')->toString()], $r);

        return redirect()->route('superadmin.creator-intelligence.content-items.show', $contentItem)->with('success', CreatorIntelligenceLabels::for($contentItem->creatorChannel)->contentItem.' updated.');
    }

    public function destroy(ContentItem $contentItem): RedirectResponse
    {
        $channelId = $contentItem->creator_channel_id;
        $label = CreatorIntelligenceLabels::for($contentItem->creatorChannel)->contentItem;
        $contentItem->delete();

        return redirect()->route('superadmin.creator-intelligence.content-items.index', ['creator_channel_id' => $channelId])->with('success', "{$label} deleted and detached from videos.");
    }

    private function data(ContentItemRequest $r, NameNormalizer $n, ?ContentItem $item = null): array
    {
        $data = $r->validated();
        $data['name'] = $n->display($data['name']);
        $data['normalized_name'] = $n->normalize($data['name']);
        $data['slug'] = $n->slug($data['name']);
        $exists = ContentItem::where('creator_channel_id', $data['creator_channel_id'])->where(fn ($q) => $q->where('normalized_name', $data['normalized_name'])->orWhere('slug', $data['slug']))->when($item, fn ($q) => $q->whereKeyNot($item->id))->exists();
        if ($exists) {
            $label = CreatorIntelligenceLabels::for(CreatorChannel::find($data['creator_channel_id']))->lowerContentItem();
            throw ValidationException::withMessages(['name' => "This {$label} already exists for the selected creator channel."]);
        }

        return $data;
    }
}
