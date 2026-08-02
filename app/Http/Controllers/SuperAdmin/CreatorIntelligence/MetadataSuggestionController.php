<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Models\MetadataSuggestion;
use App\Models\Subject;
use App\Services\CreatorIntelligence\Metadata\MetadataSuggestionApprovalService;
use App\Services\CreatorIntelligence\Metadata\MetadataSuggestionGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MetadataSuggestionController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->filtered($request)->with(['video.channel', 'video.primarySubject', 'video.primaryContentItem', 'suggestedSubject', 'suggestedContentItem']);
        $summary = (clone $query)->selectRaw('confidence, suggestion_type, count(*) aggregate')->where('status', 'pending')->groupBy('confidence', 'suggestion_type')->get();
        $unresolved = CreatorVideo::query()->when($request->filled('creator_channel_id'), fn ($q) => $q->where('creator_channel_id', $request->integer('creator_channel_id')))->whereDoesntHave('primarySubject')->whereDoesntHave('metadataSuggestions', fn ($q) => $q->where('status', 'pending')->where('suggestion_type', 'subject'))->count();

        return view('super-admin.creator-intelligence.metadata-suggestions.index', [
            'suggestions' => $query->orderByRaw("CASE confidence WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")->orderByDesc('id')->paginate(25)->withQueryString(),
            'channels' => CreatorChannel::orderBy('channel_name')->get(), 'subjectsByChannel' => Subject::where('is_active', true)->orderBy('name')->get()->groupBy('creator_channel_id'), 'summary' => $summary, 'unresolved' => $unresolved,
        ]);
    }

    public function generate(Request $request, MetadataSuggestionGenerator $generator): RedirectResponse
    {
        $data = $request->validate(['creator_channel_id' => ['nullable', 'integer', 'exists:creator_channels,id']]);
        $query = CreatorVideo::query()->when($data['creator_channel_id'] ?? null, fn ($q, $id) => $q->where('creator_channel_id', $id))
            ->where(fn ($q) => $q->whereDoesntHave('primarySubject')->orWhere(fn ($q) => $q->whereDoesntHave('primaryContentItem')->where('content_item_not_applicable', false)));
        $result = $generator->generate($query);

        return back()->with('success', "Suggestions generated: {$result['created']} created, {$result['updated']} refreshed, {$result['unresolved']} unresolved.");
    }

    public function bulk(Request $request, MetadataSuggestionApprovalService $approval): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'approve_high', 'reject', 'not_applicable'])],
            'suggestion_ids' => ['nullable', 'array', 'max:500'], 'suggestion_ids.*' => ['integer', 'distinct', 'exists:metadata_suggestions,id'],
            'suggestion_values' => ['nullable', 'array'], 'suggestion_values.*' => ['nullable', 'string', 'max:255'],
            'suggested_subject_ids' => ['nullable', 'array'], 'suggested_subject_ids.*' => ['nullable', 'integer', 'exists:subjects,id'],
            'confirmed' => ['accepted'], 'replace_primary' => ['nullable', 'boolean'],
        ]);
        if ($data['action'] === 'approve_high') {
            $ids = $this->filtered($request)->where('confidence', 'high')->where('status', 'pending')->limit(500)->pluck('id')->all();
        } else {
            $ids = $data['suggestion_ids'] ?? [];
        }
        abort_if($ids === [], 422, 'Select at least one suggestion.');
        foreach (($data['suggestion_values'] ?? []) as $id => $value) {
            if (in_array((int) $id, $ids, true) && filled($value)) {
                MetadataSuggestion::whereKey($id)->where('status', 'pending')->update(['suggested_display_value' => trim($value), 'suggested_content_item_id' => null]);
            }
        }
        foreach (($data['suggested_subject_ids'] ?? []) as $id => $subjectId) {
            if (! in_array((int) $id, $ids, true) || ! $subjectId) {
                continue;
            }
            $suggestion = MetadataSuggestion::with('video')->whereKey($id)->where('status', 'pending')->first();
            $subject = Subject::find($subjectId);
            if ($suggestion && $subject?->creator_channel_id === $suggestion->video->creator_channel_id) {
                $suggestion->update(['suggested_subject_id' => $subject->id, 'suggested_display_value' => $subject->name]);
            }
        }
        $count = match ($data['action']) {
            'approve', 'approve_high' => $approval->approve($ids, $request->user()->id, $request->boolean('replace_primary')),
            'reject' => $approval->reject($ids, $request->user()->id),
            'not_applicable' => $approval->markNotApplicable(MetadataSuggestion::whereKey($ids)->pluck('creator_video_id')->all(), $request->user()->id),
        };

        return back()->with('success', "Bulk suggestion action complete: {$count} records updated.");
    }

    private function filtered(Request $request): Builder
    {
        return MetadataSuggestion::query()
            ->when($request->filled('creator_channel_id'), fn ($q) => $q->whereHas('video', fn ($v) => $v->where('creator_channel_id', $request->integer('creator_channel_id'))))
            ->when(in_array($request->confidence, ['high', 'medium', 'low'], true), fn ($q) => $q->where('confidence', $request->confidence))
            ->when(in_array($request->suggestion_type, ['subject', 'content_item'], true), fn ($q) => $q->where('suggestion_type', $request->suggestion_type))
            ->when(in_array($request->status, ['pending', 'approved', 'rejected', 'applied'], true), fn ($q) => $q->where('status', $request->status))
            ->when($request->boolean('missing_subject'), fn ($q) => $q->whereHas('video', fn ($v) => $v->whereDoesntHave('primarySubject')))
            ->when($request->boolean('missing_content_item'), fn ($q) => $q->whereHas('video', fn ($v) => $v->whereDoesntHave('primaryContentItem')->where('content_item_not_applicable', false)))
            ->when($request->filled('search'), fn ($q) => $q->whereHas('video', fn ($v) => $v->where('title', 'like', '%'.trim($request->string('search')).'%')));
    }
}
