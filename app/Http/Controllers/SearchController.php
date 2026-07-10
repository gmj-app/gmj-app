<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\Recommendation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $searchable = mb_strlen($query) >= 2;
        $like = '%'.$this->escapeLike(mb_strtolower($query)).'%';

        $creators = Creator::query()
            ->active()
            ->when($searchable, function (Builder $creatorQuery) use ($like): void {
                $creatorQuery->where(function (Builder $query) use ($like): void {
                    $this->applyCreatorMatch($query, $like)
                        ->orWhereHas('recommendations', function (Builder $query) use ($like): void {
                            $query->whereIn('status', Recommendation::PUBLIC_STATUSES)
                                ->where(fn (Builder $query) => $this->applyRecommendationMatch($query, $like));
                        });
                });
            }, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->orderBy('display_name')
            ->paginate(12)
            ->withQueryString();

        $matchingRecommendations = collect();

        if ($searchable && $creators->isNotEmpty()) {
            $matchingRecommendations = Recommendation::query()
                ->whereIn('creator_id', $creators->pluck('id'))
                ->whereIn('status', Recommendation::PUBLIC_STATUSES)
                ->where(fn (Builder $recommendationQuery) => $this->applyRecommendationMatch($recommendationQuery, $like))
                ->withSum('userPicks as user_picks_count', 'vote_count')
                ->orderByRaw(
                    'case
                        when lower(title) = ? then 1
                        when lower(title) like ? or lower(coalesce(artist, ?)) like ? or lower(coalesce(channel_title, ?)) like ? then 2
                        when lower(coalesce(published_title, ?)) like ? or lower(coalesce(published_channel, ?)) like ? then 3
                        when lower(coalesce(category, ?)) like ? then 4
                        when lower(coalesce(description, ?)) like ? or lower(coalesce(reason, ?)) like ? then 5
                        else 6
                    end',
                    [mb_strtolower($query), $like, '', $like, '', $like, '', $like, '', $like, '', $like, '', $like, '', $like],
                )
                ->orderByRaw("case when status in ('approved', 'coming_soon', 'scheduled', 'recorded') then 0 else 1 end")
                ->orderByDesc('user_picks_count')
                ->latest()
                ->get()
                ->groupBy('creator_id');
        }

        return view('search.index', compact('creators', 'matchingRecommendations', 'query', 'searchable'));
    }

    private function applyCreatorMatch(Builder $query, string $like): Builder
    {
        return $query
            ->whereRaw('lower(display_name) like ? escape ?', [$like, '\\'])
            ->orWhereRaw('lower(slug) like ? escape ?', [$like, '\\'])
            ->orWhereRaw("lower(coalesce(channel_url, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(youtube_channel_title, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(youtube_channel_url, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(bio, '')) like ? escape ?", [$like, '\\'])
            ->orWhereHas('creatorTags', fn (Builder $query) => $query->whereRaw('lower(name) like ? escape ?', [$like, '\\']));
    }

    private function applyRecommendationMatch(Builder $query, string $like): Builder
    {
        return $query
            ->whereRaw('lower(title) like ? escape ?', [$like, '\\'])
            ->orWhereRaw("lower(coalesce(artist, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(channel_title, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(youtube_url, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(normalized_url, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(category, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(description, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(reason, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(published_title, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(published_channel, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(published_reaction_url, '')) like ? escape ?", [$like, '\\'])
            ->orWhereRaw("lower(coalesce(published_normalized_url, '')) like ? escape ?", [$like, '\\'])
            ->orWhereHas('creatorTags', fn (Builder $query) => $query->whereRaw('lower(name) like ? escape ?', [$like, '\\']));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
