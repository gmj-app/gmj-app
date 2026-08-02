<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Models\CreatorChannel;
use App\Models\CreatorProfile;
use App\Models\CreatorVideo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetadataQueueController extends Controller
{
    public function __invoke(Request $r): View
    {
        $q = CreatorVideo::with(['channel.profile', 'primarySubject', 'primaryContentItem', 'titleMetadata', 'thumbnailMetadata', 'editorialMetadata']);
        if ($r->filled('creator_channel_id')) {
            $q->where('creator_channel_id', $r->integer('creator_channel_id'));
        }if ($r->filled('creator_profile_id')) {
            $q->whereHas('channel', fn ($x) => $x->where('creator_profile_id', $r->integer('creator_profile_id')));
        }if (in_array($r->status, ['not_started', 'in_progress', 'complete'], true)) {
            $q->where('metadata_completion_status', $r->status);
        }if (is_numeric($r->min_completion)) {
            $q->where('metadata_completion_percentage', '>=', $r->min_completion);
        }if (is_numeric($r->max_completion)) {
            $q->where('metadata_completion_percentage', '<=', $r->max_completion);
        }foreach (['missing_subject' => 'primarySubject', 'missing_content_item' => 'primaryContentItem'] as $p => $rel) {
            if ($r->boolean($p)) {
                $q->whereDoesntHave($rel);
            }
        }foreach (['title_unreviewed' => 'titleMetadata', 'thumbnail_unreviewed' => 'thumbnailMetadata', 'editorial_unreviewed' => 'editorialMetadata'] as $p => $rel) {
            if ($r->boolean($p)) {
                $q->where(fn (Builder $x) => $x->whereDoesntHave($rel)->orWhereHas($rel, fn ($m) => $m->whereNull('reviewed_at')));
            }
        }

        return view('super-admin.creator-intelligence.metadata-queue.index', ['videos' => $q->orderBy('metadata_completion_percentage')->orderByDesc('published_at')->paginate(25)->withQueryString(), 'profiles' => CreatorProfile::orderBy('display_name')->get(), 'channels' => CreatorChannel::orderBy('channel_name')->get()]);
    }
}
