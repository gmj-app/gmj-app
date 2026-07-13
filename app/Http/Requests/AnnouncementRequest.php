<?php

namespace App\Http\Requests;

use App\Models\Announcement;
use App\Services\NotificationUrlResolver;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'internal_name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:1000'],
            'audience' => ['required', Rule::in(Announcement::AUDIENCES)],
            'action_url' => ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                if (filled($value) && ! app(NotificationUrlResolver::class)->isSafe((string) $value)) {
                    $fail('Use a safe internal path beginning with /.');
                }
            }],
            'action_label' => ['nullable', 'string', 'max:80'],
            'icon' => ['required', Rule::in(config('notifications.icons', []))],
            'severity' => ['required', Rule::in(config('notifications.severities', []))],
            'publish_timing' => ['required', Rule::in(['draft', 'now', 'schedule'])],
            'starts_at' => [Rule::requiredIf($this->input('publish_timing') === 'schedule'), 'nullable', 'date', 'after:now'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    protected function passedValidation(): void
    {
        if (filled($this->validated('starts_at')) && filled($this->validated('expires_at')) && strtotime($this->validated('expires_at')) <= strtotime($this->validated('starts_at'))) {
            throw ValidationException::withMessages(['expires_at' => 'The expiry must be after the scheduled publish time.']);
        }
    }
}
