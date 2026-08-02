<?php

namespace App\Services\CreatorIntelligence\Videos;

use App\Enums\PerformanceSnapshotSource;
use App\Enums\VideoContentType;
use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoFormat;
use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CreatorVideoQuery
{
    private const DIRECT_SORTS = ['title', 'published_at', 'duration_seconds', 'video_format', 'content_type', 'created_at', 'updated_at'];

    private const METRIC_SORTS = ['views', 'impressions', 'impressions_ctr', 'watch_time_minutes', 'average_view_duration_seconds', 'average_percentage_viewed', 'subscribers_gained', 'estimated_revenue', 'hype_points', 'snapshot_date'];

    public function __construct(private readonly LatestSnapshotResolver $latest) {}

    public function build(Request $request): Builder
    {
        $query = CreatorVideo::query()->with(['channel.profile'])->withCount('importRows')->select('creator_videos.*');
        foreach (LatestSnapshotResolver::FIELDS as $field) {
            $query->selectSub($this->latest->subquery($field), 'latest_'.$field);
        }

        if ($search = trim((string) $request->input('q'))) {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $query->where(fn (Builder $q) => $q->where('title', 'like', $term)->orWhere('platform_video_id', 'like', $term)->orWhere('video_url', 'like', $term)->orWhere('description', 'like', $term)->orWhereHas('channel', fn (Builder $c) => $c->where('channel_name', 'like', $term)->orWhereHas('profile', fn (Builder $p) => $p->where('display_name', 'like', $term))));
        }
        $this->integerFilter($query, $request, 'creator_profile_id', fn (Builder $q, int $value) => $q->whereHas('channel', fn (Builder $c) => $c->where('creator_profile_id', $value)));
        $this->integerFilter($query, $request, 'creator_channel_id', fn (Builder $q, int $value) => $q->where('creator_channel_id', $value));
        $this->dateRange($query, $request, 'published_at', 'published_from', 'published_to');
        $this->enumFilter($query, $request, 'video_format', VideoFormat::class);
        $this->enumFilter($query, $request, 'content_type', VideoContentType::class);
        $this->enumFilter($query, $request, 'copyright_status', VideoCopyrightStatus::class);
        foreach (['is_premiere', 'is_live', 'is_short', 'is_documentary', 'is_interview'] as $field) {
            $this->booleanFilter($query, $request, $field);
        }
        if (in_array($request->input('is_monetized'), ['1', '0'], true)) {
            $query->where('is_monetized', $request->input('is_monetized') === '1');
        } elseif ($request->input('is_monetized') === 'unknown') {
            $query->whereNull('is_monetized');
        }
        foreach (['thumbnail_url' => 'has_thumbnail', 'description' => 'has_description', 'published_at' => 'has_published_date', 'duration_seconds' => 'has_duration'] as $column => $parameter) {
            $this->presenceFilter($query, $request, $column, $parameter);
        }
        if (in_array($request->input('has_performance_snapshot'), ['1', '0'], true)) {
            $query->{$request->input('has_performance_snapshot') === '1' ? 'whereHas' : 'whereDoesntHave'}('performanceSnapshots');
        }
        if (in_array($request->input('has_import_history'), ['1', '0'], true)) {
            $query->{$request->input('has_import_history') === '1' ? 'whereHas' : 'whereDoesntHave'}('importRows');
        }

        foreach (['views', 'impressions', 'impressions_ctr', 'watch_time_minutes', 'average_percentage_viewed', 'subscribers_gained', 'estimated_revenue', 'hype_points'] as $metric) {
            foreach (['min' => '>=', 'max' => '<='] as $prefix => $operator) {
                if (is_numeric($request->input($prefix.'_'.$metric))) {
                    $query->where($this->latest->subquery($metric), $operator, $request->input($prefix.'_'.$metric));
                }
            }
        }
        if (PerformanceSnapshotSource::tryFrom((string) $request->input('snapshot_source'))) {
            $query->where($this->latest->subquery('source'), $request->input('snapshot_source'));
        }
        if ($request->filled('snapshot_from')) {
            $query->where($this->latest->subquery('snapshot_date'), '>=', $request->input('snapshot_from'));
        }
        if ($request->filled('snapshot_to')) {
            $query->where($this->latest->subquery('snapshot_date'), '<=', $request->input('snapshot_to'));
        }

        $sort = (string) $request->input('sort', 'published_at');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        if ($sort === 'channel') {
            return $query->orderBy(CreatorChannel::select('channel_name')->whereColumn('creator_channels.id', 'creator_videos.creator_channel_id')->limit(1), $direction)->orderBy('creator_videos.id');
        }
        if (in_array($sort, self::METRIC_SORTS, true)) {
            return $query->orderByRaw('latest_'.$sort.' IS NULL')->orderBy('latest_'.$sort, $direction)->orderBy('creator_videos.id');
        }
        if (! in_array($sort, self::DIRECT_SORTS, true)) {
            $sort = 'published_at';
        }

        return $query->orderByRaw('creator_videos.'.$sort.' IS NULL')->orderBy('creator_videos.'.$sort, $direction)->orderBy('creator_videos.id');
    }

    public function perPage(Request $request): int
    {
        return in_array((int) $request->input('per_page'), [25, 50, 100], true) ? (int) $request->input('per_page') : 25;
    }

    private function enumFilter(Builder $q, Request $r, string $field, string $enum): void
    {
        if ($enum::tryFrom((string) $r->input($field))) {
            $q->where($field, $r->input($field));
        }
    }

    private function integerFilter(Builder $q, Request $r, string $field, callable $apply): void
    {
        if (filter_var($r->input($field), FILTER_VALIDATE_INT)) {
            $apply($q, (int) $r->input($field));
        }
    }

    private function booleanFilter(Builder $q, Request $r, string $field): void
    {
        if (in_array($r->input($field), ['1', '0'], true)) {
            $q->where($field, $r->input($field) === '1');
        }
    }

    private function presenceFilter(Builder $q, Request $r, string $column, string $parameter): void
    {
        if ($r->input($parameter) === '1') {
            $q->whereNotNull($column)->where($column, '!=', '');
        } elseif ($r->input($parameter) === '0') {
            $q->where(fn (Builder $x) => $x->whereNull($column)->orWhere($column, ''));
        }
    }

    private function dateRange(Builder $q, Request $r, string $column, string $from, string $to): void
    {
        if ($r->filled($from)) {
            $q->whereDate($column, '>=', $r->input($from));
        } if ($r->filled($to)) {
            $q->whereDate($column, '<=', $r->input($to));
        }
    }
}
