<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnnouncementRequest;
use App\Models\Announcement;
use App\Services\AnnouncementAudienceResolver;
use App\Services\AnnouncementPublicationService;
use App\Services\SuperAdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementPublicationService $publisher,
        private readonly AnnouncementAudienceResolver $audiences,
        private readonly SuperAdminAuditService $audit,
    ) {}

    public function index(): View
    {
        $announcements = Announcement::query()->with('creator:id,name,public_display_name')->latest()->paginate(20);

        return view('super-admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        $announcement = new Announcement([
            'audience' => Announcement::AUDIENCE_ALL,
            'action_url' => '/notifications',
            'action_label' => 'View update',
            'icon' => 'megaphone',
            'severity' => 'info',
            'status' => Announcement::STATUS_DRAFT,
        ]);
        $estimates = $this->estimates();

        return view('super-admin.announcements.create', compact('announcement', 'estimates'));
    }

    public function store(AnnouncementRequest $request): RedirectResponse
    {
        $data = $this->data($request);
        $data['created_by_user_id'] = $request->user()->id;
        $data['updated_by_user_id'] = $request->user()->id;
        $data['status'] = $request->validated('publish_timing') === 'schedule'
            ? Announcement::STATUS_SCHEDULED
            : Announcement::STATUS_DRAFT;
        $announcement = Announcement::query()->create($data);
        $this->audit->record($request->user(), $announcement, 'announcement.created', 'Announcement created.', [], $announcement->only(['internal_name', 'audience', 'status', 'starts_at', 'expires_at']), ['audience' => $announcement->audience], $request);

        if ($announcement->status === Announcement::STATUS_SCHEDULED) {
            $this->audit->record($request->user(), $announcement, 'announcement.scheduled', 'Announcement scheduled.', [], ['starts_at' => $announcement->starts_at], ['audience' => $announcement->audience], $request);
        }
        if ($request->validated('publish_timing') === 'now') {
            $this->queueAndAudit($request, $announcement);

            return redirect()->route('super-admin.announcements.index')->with('success', 'Announcement queued for delivery.');
        }

        return redirect()->route('super-admin.announcements.index')->with('success', $announcement->status === Announcement::STATUS_SCHEDULED ? 'Announcement scheduled.' : 'Announcement draft created.');
    }

    public function edit(Announcement $announcement): View
    {
        abort_unless($announcement->isEditable(), 409);
        $estimates = $this->estimates();

        return view('super-admin.announcements.edit', compact('announcement', 'estimates'));
    }

    public function update(AnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($announcement->isEditable(), 409);
        $before = $announcement->only(['internal_name', 'title', 'message', 'audience', 'action_url', 'action_label', 'icon', 'severity', 'status', 'starts_at', 'expires_at']);
        $data = $this->data($request);
        $data['updated_by_user_id'] = $request->user()->id;
        $data['status'] = $request->validated('publish_timing') === 'schedule' ? Announcement::STATUS_SCHEDULED : Announcement::STATUS_DRAFT;
        $announcement->update($data);
        $after = $announcement->fresh()->only(array_keys($before));
        $changedFields = collect($after)->filter(fn ($value, $key): bool => $value != ($before[$key] ?? null))->keys()->all();
        $this->audit->record($request->user(), $announcement, 'announcement.updated', 'Announcement updated.', $before, $after, ['audience' => $announcement->audience, 'changed_fields' => $changedFields], $request);

        if ($announcement->status === Announcement::STATUS_SCHEDULED) {
            $this->audit->record($request->user(), $announcement, 'announcement.scheduled', 'Announcement scheduled.', $before, $after, ['audience' => $announcement->audience], $request);
        }
        if ($request->validated('publish_timing') === 'now') {
            $this->queueAndAudit($request, $announcement);

            return redirect()->route('super-admin.announcements.index')->with('success', 'Announcement queued for delivery.');
        }

        return redirect()->route('super-admin.announcements.index')->with('success', 'Announcement updated.');
    }

    public function publish(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($announcement->isEditable(), 409);
        if ($announcement->starts_at?->isFuture()) {
            $announcement->update(['starts_at' => null, 'updated_by_user_id' => $request->user()->id]);
        }
        if (! $this->queueAndAudit($request, $announcement)) {
            return back()->with('success', 'Announcement was not queued because it has expired or is already being delivered.');
        }

        return back()->with('success', 'Announcement queued for delivery.');
    }

    public function cancel(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless(in_array($announcement->status, [Announcement::STATUS_DRAFT, Announcement::STATUS_SCHEDULED], true), 409);
        $before = ['status' => $announcement->status];
        $announcement->update(['status' => Announcement::STATUS_CANCELLED, 'updated_by_user_id' => $request->user()->id]);
        $this->audit->record($request->user(), $announcement, 'announcement.cancelled', 'Announcement cancelled.', $before, ['status' => Announcement::STATUS_CANCELLED], ['audience' => $announcement->audience], $request);

        return back()->with('success', 'Announcement cancelled.');
    }

    public function duplicate(Request $request, Announcement $announcement): RedirectResponse
    {
        $copy = $announcement->replicate(['status', 'starts_at', 'published_at', 'recipient_count', 'delivered_count', 'failed_count']);
        $copy->internal_name = $announcement->internal_name.' (copy)';
        $copy->status = Announcement::STATUS_DRAFT;
        $copy->starts_at = null;
        $copy->expires_at = null;
        $copy->created_by_user_id = $request->user()->id;
        $copy->updated_by_user_id = $request->user()->id;
        $copy->save();
        $this->audit->record($request->user(), $copy, 'announcement.created', 'Announcement duplicated as a draft.', [], ['source_announcement_id' => $announcement->id], ['audience' => $copy->audience], $request);

        return redirect()->route('super-admin.announcements.edit', $copy)->with('success', 'Announcement duplicated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        abort_unless($announcement->isEditable() && $announcement->deliveries()->doesntExist(), 409);
        $announcement->delete();

        return redirect()->route('super-admin.announcements.index')->with('success', 'Announcement deleted.');
    }

    private function data(AnnouncementRequest $request): array
    {
        $data = Arr::except($request->validated(), ['publish_timing']);
        foreach (['internal_name', 'title', 'message', 'action_label'] as $key) {
            $data[$key] = filled($data[$key] ?? null) ? trim(strip_tags((string) $data[$key])) : null;
        }
        $data['action_url'] = filled($data['action_url'] ?? null) ? trim((string) $data['action_url']) : null;
        if ($request->validated('publish_timing') !== 'schedule') {
            $data['starts_at'] = null;
        }

        return $data;
    }

    private function estimates(): array
    {
        return [
            Announcement::AUDIENCE_ALL => $this->audiences->count(Announcement::AUDIENCE_ALL),
            Announcement::AUDIENCE_CREATORS => $this->audiences->count(Announcement::AUDIENCE_CREATORS),
        ];
    }

    private function queueAndAudit(Request $request, Announcement $announcement): bool
    {
        $estimatedRecipients = $this->audiences->count($announcement->audience);
        if (! $this->publisher->queue($announcement)) {
            return false;
        }
        $this->audit->record($request->user(), $announcement, 'announcement.published', 'Announcement queued for publication.', ['status' => $announcement->status], ['status' => Announcement::STATUS_PUBLISHING], ['audience' => $announcement->audience, 'recipient_count' => $estimatedRecipients], $request);

        return true;
    }
}
