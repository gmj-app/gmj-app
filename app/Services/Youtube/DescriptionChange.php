<?php

namespace App\Services\Youtube;

class DescriptionChange
{
    public function __construct(
        public readonly string $videoId,
        public readonly string $videoTitle,
        public readonly string $oldDescription,
        public readonly string $newDescription,
        public readonly string $action,
        public readonly string $status,
        public readonly ?string $message = null,
    ) {}

    public function changed(): bool
    {
        return $this->status === 'changed';
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'video_id' => $this->videoId,
            'video_title' => $this->videoTitle,
            'old_description' => $this->oldDescription,
            'new_description' => $this->newDescription,
            'action' => $this->action,
            'status' => $this->status,
            'message' => $this->message,
        ];
    }

    /**
     * @param  array<string, string|null>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            videoId: (string) $data['video_id'],
            videoTitle: (string) $data['video_title'],
            oldDescription: (string) $data['old_description'],
            newDescription: (string) $data['new_description'],
            action: (string) $data['action'],
            status: (string) $data['status'],
            message: $data['message'] ?? null,
        );
    }
}
