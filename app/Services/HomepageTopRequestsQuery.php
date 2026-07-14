<?php

namespace App\Services;

use App\Models\Recommendation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HomepageTopRequestsQuery
{
    public const PER_CREATOR = 3;

    /**
     * @param  Collection<int, int>|array<int, int>  $creatorIds
     * @return Collection<int, Collection<int, Recommendation>>
     */
    public function get(Collection|array $creatorIds): Collection
    {
        $ids = collect($creatorIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return $this->builder($ids)
            ->get()
            ->map(fn (object $row) => (new Recommendation)->newFromBuilder((array) $row))
            ->groupBy('creator_id')
            ->map->values();
    }

    /** @param Collection<int, int>|array<int, int> $creatorIds */
    public function builder(Collection|array $creatorIds): Builder
    {
        $ids = collect($creatorIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        $aggregates = Recommendation::query()
            ->select([
                'recommendations.id',
                'recommendations.creator_id',
                'recommendations.submitted_by',
                'recommendations.submission_source',
                'recommendations.title',
                'recommendations.source_title',
                'recommendations.display_title_override',
                'recommendations.is_pinned',
                'recommendations.created_at',
            ])
            ->whereIn('recommendations.creator_id', $ids)
            ->publiclyVisible()
            ->votable()
            ->where(fn ($query) => $query
                ->whereNull('recommendations.moderation_status')
                ->orWhere('recommendations.moderation_status', '!=', 'removed'))
            ->withSum('userPicks as user_picks_count', 'vote_count');

        // The aggregate alias exists as a real derived-table column before the
        // window expression orders by it. This is valid on MySQL 8 and SQLite.
        $ranked = DB::query()
            ->fromSub($aggregates, 'request_aggregates')
            ->select('request_aggregates.*')
            ->selectRaw(
                'ROW_NUMBER() OVER (PARTITION BY creator_id ORDER BY COALESCE(user_picks_count, 0) DESC, is_pinned DESC, created_at DESC, id DESC) AS creator_rank'
            );

        return DB::query()
            ->fromSub($ranked, 'ranked_requests')
            ->where('creator_rank', '<=', self::PER_CREATOR)
            ->orderBy('creator_id')
            ->orderBy('creator_rank');
    }
}
