<?php

namespace App\Http\Requests\CreatorIntelligence;

use App\Enums\CreatorSentiment;
use App\Enums\ReactionStyle;
use App\Enums\SubjectRelationshipType;
use App\Enums\VideoContentType;
use App\Enums\VideoCopyrightStatus;
use App\Models\CreatorVideo;
use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkUpdateVideoMetadataRequest extends FormRequest
{
    private const OPERATIONS = [
        'assign_subject',
        'assign_primary_subject',
        'subject_relationship_type',
        'content_type',
        'creator_sentiment',
        'reaction_style',
        'copyright_status',
        'is_monetized',
        'review_title',
        'review_thumbnail',
        'review_editorial',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    public function rules(): array
    {
        return [
            'video_ids' => ['required', 'array', 'min:1', 'max:500'],
            'video_ids.*' => ['integer', 'distinct', 'exists:creator_videos,id'],
            'operation' => ['required', Rule::in(self::OPERATIONS)],
            'value' => ['nullable'],
            'mode' => ['required', Rule::in(['fill', 'replace'])],
            'confirmed' => ['accepted'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $operation = $this->string('operation')->toString();
            $value = $this->input('value');
            if (! str_starts_with($operation, 'review_') && ($value === null || $value === '')) {
                $validator->errors()->add('value', 'Select a value for this bulk action.');

                return;
            }

            $allowedValues = match ($operation) {
                'subject_relationship_type' => array_column(SubjectRelationshipType::cases(), 'value'),
                'content_type' => array_column(VideoContentType::cases(), 'value'),
                'creator_sentiment' => array_column(CreatorSentiment::cases(), 'value'),
                'reaction_style' => array_column(ReactionStyle::cases(), 'value'),
                'copyright_status' => array_column(VideoCopyrightStatus::cases(), 'value'),
                'is_monetized' => ['0', '1', 0, 1],
                default => null,
            };
            if ($allowedValues !== null && ! in_array($value, $allowedValues, true)) {
                $validator->errors()->add('value', 'The selected value is invalid for this bulk action.');
            }

            if (! in_array($operation, ['assign_subject', 'assign_primary_subject'], true)) {
                return;
            }

            $subject = Subject::find($value);
            if (! $subject) {
                $validator->errors()->add('value', 'Select an available subject.');

                return;
            }

            $channelIds = CreatorVideo::whereKey($this->input('video_ids', []))->distinct()->pluck('creator_channel_id');
            if ($channelIds->count() !== 1) {
                $validator->errors()->add('video_ids', 'Subject assignment requires selected videos from one creator channel.');

                return;
            }
            if ((int) $channelIds->first() !== $subject->creator_channel_id) {
                $validator->errors()->add('value', 'The selected subject does not belong to the selected videos’ creator channel.');
            }
        }];
    }
}
