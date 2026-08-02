<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Enums\CreatorSentiment;
use App\Enums\ReactionStyle;
use App\Enums\SubjectRelationshipType;
use App\Enums\VideoContentType;
use App\Enums\VideoCopyrightStatus;
use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\CreatorChannel;
use App\Models\CreatorProfile;
use App\Models\CreatorVideo;
use App\Models\Subject;
use App\Support\CreatorIntelligenceLabels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetadataQueueController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = CreatorVideo::query()->with(['channel.profile', 'primarySubject', 'primaryContentItem', 'titleMetadata', 'thumbnailMetadata', 'editorialMetadata']);
        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function (Builder $videos) use ($search): void {
                $videos->where('title', 'like', '%'.$search.'%')
                    ->orWhere('platform_video_id', 'like', '%'.$search.'%')
                    ->orWhereHas('subjects', fn (Builder $subjects) => $subjects->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('contentItems', fn (Builder $items) => $items->where('name', 'like', '%'.$search.'%'));
            });
        }
        if ($request->filled('creator_channel_id')) {
            $query->where('creator_channel_id', $request->integer('creator_channel_id'));
        }
        if ($request->filled('creator_profile_id')) {
            $query->whereHas('channel', fn (Builder $channel) => $channel->where('creator_profile_id', $request->integer('creator_profile_id')));
        }
        if (in_array($request->status, ['not_started', 'in_progress', 'complete'], true)) {
            $query->where('metadata_completion_status', $request->status);
        }
        if (is_numeric($request->min_completion)) {
            $query->where('metadata_completion_percentage', '>=', $request->min_completion);
        }
        if (is_numeric($request->max_completion)) {
            $query->where('metadata_completion_percentage', '<=', $request->max_completion);
        }
        foreach (['missing_subject' => 'primarySubject', 'missing_content_item' => 'primaryContentItem'] as $parameter => $relation) {
            if ($request->boolean($parameter)) {
                $query->whereDoesntHave($relation);
            }
        }
        foreach (['title_unreviewed' => 'titleMetadata', 'thumbnail_unreviewed' => 'thumbnailMetadata', 'editorial_unreviewed' => 'editorialMetadata'] as $parameter => $relation) {
            if ($request->boolean($parameter)) {
                $query->where(fn (Builder $metadata) => $metadata->whereDoesntHave($relation)->orWhereHas($relation, fn (Builder $record) => $record->whereNull('reviewed_at')));
            }
        }

        $sort = in_array($request->sort, ['metadata_completion_percentage', 'published_at', 'title'], true) ? $request->sort : 'metadata_completion_percentage';
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $direction);
        if ($sort !== 'published_at') {
            $query->orderByDesc('published_at');
        }

        $videos = $query->paginate(25)->withQueryString();
        $totalVideos = CreatorVideo::count();
        $selectedChannel = $request->filled('creator_channel_id') ? CreatorChannel::find($request->integer('creator_channel_id')) : null;
        $subjects = Subject::query()->with('creatorChannel')->when($selectedChannel, fn (Builder $subjects) => $subjects->where('creator_channel_id', $selectedChannel->id))->orderBy('name')->get();
        $contentItemsExist = ContentItem::query()->when($selectedChannel, fn (Builder $items) => $items->where('creator_channel_id', $selectedChannel->id))->exists();
        $missingFilter = collect(['missing_subject', 'missing_content_item', 'title_unreviewed', 'thumbnail_unreviewed', 'editorial_unreviewed'])->contains(fn (string $filter) => $request->boolean($filter))
            || in_array($request->status, ['not_started', 'in_progress'], true);
        $emptyState = match (true) {
            $totalVideos === 0 => 'no_imported_videos',
            $videos->isEmpty() && $missingFilter => 'no_missing_metadata',
            $videos->isEmpty() => 'no_results',
            default => null,
        };
        $labels = CreatorIntelligenceLabels::for($selectedChannel);

        return view('super-admin.creator-intelligence.metadata-queue.index', [
            'videos' => $videos,
            'profiles' => CreatorProfile::orderBy('display_name')->get(),
            'channels' => CreatorChannel::orderBy('channel_name')->get(),
            'subjects' => $subjects,
            'subjectsExist' => $subjects->isNotEmpty(),
            'contentItemsExist' => $contentItemsExist,
            'selectedChannel' => $selectedChannel,
            'labels' => $labels,
            'subjectLabel' => $labels->subject,
            'contentItemLabel' => $labels->contentItem,
            'relationshipTypes' => SubjectRelationshipType::cases(),
            'contentTypes' => VideoContentType::cases(),
            'sentiments' => CreatorSentiment::cases(),
            'reactionStyles' => ReactionStyle::cases(),
            'copyrights' => VideoCopyrightStatus::cases(),
            'emptyState' => $emptyState,
            'totalVideos' => $totalVideos,
        ]);
    }
}
