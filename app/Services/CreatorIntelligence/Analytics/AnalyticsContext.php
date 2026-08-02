<?php

namespace App\Services\CreatorIntelligence\Analytics;

use App\Enums\PerformanceSnapshotSource;
use App\Enums\VideoContentType;
use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoFormat;
use App\Models\CreatorChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

final readonly class AnalyticsContext
{
    public function __construct(public array $filters, public ?CreatorChannel $channel) {}

    public static function fromRequest(Request $request): self
    {
        $defaults = ['minimum_sample_size' => 3, 'include_shorts' => true, 'include_lives' => true, 'include_without_performance' => false, 'show_low_sample' => false];
        $input = array_merge($defaults, $request->all());
        $validated = Validator::make($input, [
            'creator_profile_id' => ['nullable', 'integer', 'exists:creator_profiles,id'],
            'creator_channel_id' => ['nullable', 'integer', 'exists:creator_channels,id'],
            'published_from' => ['nullable', 'date'], 'published_to' => ['nullable', 'date', 'after_or_equal:published_from'],
            'snapshot_from' => ['nullable', 'date'], 'snapshot_to' => ['nullable', 'date', 'after_or_equal:snapshot_from'],
            'snapshot_source' => ['nullable', 'string', 'in:'.implode(',', array_column(PerformanceSnapshotSource::cases(), 'value'))],
            'video_format' => ['nullable', 'string', 'in:'.implode(',', array_column(VideoFormat::cases(), 'value'))],
            'content_type' => ['nullable', 'string', 'in:'.implode(',', array_column(VideoContentType::cases(), 'value'))],
            'copyright_status' => ['nullable', 'string', 'in:'.implode(',', array_column(VideoCopyrightStatus::cases(), 'value'))],
            'monetization_status' => ['nullable', 'in:1,0,unknown'],
            'minimum_metadata_completion' => ['nullable', 'integer', 'between:0,100'],
            'minimum_sample_size' => ['required', 'integer', 'between:1,1000'],
            'include_shorts' => ['boolean'], 'include_lives' => ['boolean'], 'include_without_performance' => ['boolean'], 'show_low_sample' => ['boolean'],
            'grouping' => ['nullable', 'in:week,month,quarter,year'], 'dimension' => ['nullable', 'string', 'max:80'],
            'include_secondary' => ['boolean'], 'include_unreviewed' => ['boolean'], 'primary_only' => ['boolean'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'], 'compare_subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
        ])->validate();
        foreach (['include_shorts', 'include_lives', 'include_without_performance', 'show_low_sample', 'include_secondary', 'include_unreviewed', 'primary_only'] as $key) {
            $validated[$key] = filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOL);
        }
        $channel = isset($validated['creator_channel_id'])
            ? CreatorChannel::with('profile')->find($validated['creator_channel_id'])
            : CreatorChannel::with('profile')->active()->when(isset($validated['creator_profile_id']), fn ($query) => $query->where('creator_profile_id', $validated['creator_profile_id']))->orderBy('id')->first();
        if ($channel && isset($validated['creator_profile_id']) && $channel->creator_profile_id !== (int) $validated['creator_profile_id']) {
            abort(422, 'The selected channel does not belong to the selected profile.');
        }

        return new self(Arr::where($validated, fn ($value) => $value !== null && $value !== ''), $channel);
    }

    public function query(): array
    {
        return array_filter($this->filters, fn ($value) => ! is_bool($value) || $value);
    }

    public function sampleMinimum(): int
    {
        return (int) $this->filters['minimum_sample_size'];
    }
}
