<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Enums\CreatorExpression;
use App\Enums\CreatorSentiment;
use App\Enums\MetadataScale;
use App\Enums\ReactionStyle;
use App\Enums\SubjectRelationshipType;
use App\Enums\ThumbnailBackgroundStyle;
use App\Enums\ThumbnailLayoutStyle;
use App\Enums\ThumbnailTextPosition;
use App\Enums\TitleTemplate;
use App\Enums\VideoContentType;
use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\UpdateCreatorVideoRequest;
use App\Models\ContentItem;
use App\Models\CreatorChannel;
use App\Models\CreatorProfile;
use App\Models\CreatorVideo;
use App\Models\Subject;
use App\Services\CreatorIntelligence\Metadata\MetadataReviewInvalidator;
use App\Services\CreatorIntelligence\Videos\CreatorVideoDataQualityService;
use App\Services\CreatorIntelligence\Videos\CreatorVideoQuery;
use App\Services\CreatorIntelligence\Videos\LatestSnapshotResolver;
use App\Services\CreatorIntelligence\Videos\MetricFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CreatorVideoController extends Controller
{
    public function index(Request $request, CreatorVideoQuery $videos): View
    {
        return view('super-admin.creator-intelligence.videos.index', ['videos' => $videos->build($request)->paginate($videos->perPage($request))->withQueryString(), 'profiles' => CreatorProfile::orderBy('display_name')->get(), 'channels' => CreatorChannel::orderBy('channel_name')->get(), 'formats' => VideoFormat::cases(), 'types' => VideoContentType::cases(), 'copyrights' => VideoCopyrightStatus::cases(), 'format' => app(MetricFormatter::class), 'activeFilterCount' => collect($request->except(['page', 'sort', 'direction', 'per_page']))->filter(fn ($value) => filled($value))->count()]);
    }

    public function show(CreatorVideo $creatorVideo, LatestSnapshotResolver $latest, CreatorVideoDataQualityService $quality): View
    {
        $creatorVideo->load(['channel.profile', 'subjects', 'contentItems', 'titleMetadata.reviewedBy', 'thumbnailMetadata', 'editorialMetadata']);
        $snapshot = $latest->resolve($creatorVideo);

        return view('super-admin.creator-intelligence.videos.show', ['video' => $creatorVideo, 'latest' => $snapshot, 'quality' => $quality->evaluate($creatorVideo, $snapshot), 'format' => app(MetricFormatter::class), 'snapshots' => $creatorVideo->performanceSnapshots()->orderByDesc('snapshot_date')->orderBy('source')->paginate(25, ['*'], 'snapshots_page'), 'imports' => $creatorVideo->importRows()->with(['batch.uploadedBy', 'snapshot'])->latest()->paginate(25, ['*'], 'imports_page'), 'availableSubjects' => Subject::where('creator_channel_id', $creatorVideo->creator_channel_id)->orderBy('name')->get(), 'availableItems' => ContentItem::where('creator_channel_id', $creatorVideo->creator_channel_id)->orderBy('name')->get(), 'relationshipTypes' => SubjectRelationshipType::cases(), 'titleTemplates' => TitleTemplate::cases(), 'expressions' => CreatorExpression::cases(), 'backgrounds' => ThumbnailBackgroundStyle::cases(), 'layouts' => ThumbnailLayoutStyle::cases(), 'textPositions' => ThumbnailTextPosition::cases(), 'sentiments' => CreatorSentiment::cases(), 'reactionStyles' => ReactionStyle::cases(), 'scales' => MetadataScale::cases()]);
    }

    public function edit(CreatorVideo $creatorVideo): View
    {
        return view('super-admin.creator-intelligence.videos.edit', ['video' => $creatorVideo, 'formats' => VideoFormat::cases(), 'types' => VideoContentType::cases(), 'copyrights' => VideoCopyrightStatus::cases()]);
    }

    public function update(UpdateCreatorVideoRequest $request, CreatorVideo $creatorVideo, MetadataReviewInvalidator $reviewInvalidator): RedirectResponse
    {
        $creatorVideo->fill($request->validated());
        $titleChanged = $creatorVideo->isDirty('title');
        $thumbnailChanged = $creatorVideo->isDirty('thumbnail_url');
        $changed = array_keys($creatorVideo->getDirty());
        $creatorVideo->save();
        $reviewInvalidator->apply($creatorVideo, $titleChanged, $thumbnailChanged);
        Log::info('Creator Intelligence video metadata corrected.', ['creator_video_id' => $creatorVideo->id, 'user_id' => $request->user()->id, 'changed_fields' => $changed]);

        return redirect()->route('superadmin.creator-intelligence.videos.show', $creatorVideo)->with('success', 'Video metadata updated.');
    }
}
