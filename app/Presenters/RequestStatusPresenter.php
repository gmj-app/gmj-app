<?php

namespace App\Presenters;

use App\Models\Recommendation;

class RequestStatusPresenter
{
    /** @return array{label: string, style_key: string, classes: string, show_compact: bool, voting_open: bool, public: bool, description: string} */
    public static function for(string $status): array
    {
        $label = Recommendation::STATUS_LABELS[$status]
            ?? str($status)->replace('_', ' ')->title()->toString();
        $styleKey = match ($status) {
            'coming_soon' => 'violet',
            'scheduled' => 'blue',
            'recorded' => 'amber',
            'published' => 'emerald',
            'already_seen' => 'slate',
            'passed' => 'rose',
            'hidden', 'withdrawn' => 'slate',
            default => 'indigo',
        };
        $public = in_array($status, Recommendation::PUBLIC_STATUSES, true);

        return [
            'label' => $label,
            'style_key' => $styleKey,
            'classes' => self::classes($styleKey),
            'show_compact' => $public && in_array($status, ['coming_soon', 'scheduled', 'recorded', 'published', 'already_seen', 'passed'], true),
            'voting_open' => Recommendation::statusConsumesUpvotes($status),
            'public' => $public,
            'description' => 'Request status: '.$label.'. '.(Recommendation::statusConsumesUpvotes($status) ? 'Voting is open.' : 'Voting is closed.'),
        ];
    }

    private static function classes(string $styleKey): string
    {
        return match ($styleKey) {
            'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
            'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
            'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
            'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            'rose' => 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
            'slate' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
            default => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
        };
    }
}
