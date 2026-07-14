<?php

namespace App\Services;

use App\Models\Creator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreatorProfileUpdateService
{
    public function __construct(private readonly CreatorTagService $tags) {}

    /** @return array{before:array,after:array,assets:array<int,string>} */
    public function update(Creator $creator, array $validated, array $files = []): array
    {
        $publicFields = ['display_name', 'slug', 'youtube_channel_url', 'bio', 'submission_instructions', 'submissions_open', 'recommendation_approval_mode', 'avatar_path', 'hero_path'];
        $before = $creator->only($publicFields);
        $newPaths = $oldPaths = $assets = [];

        $updates = collect($validated)->only($publicFields)->all();
        $updates['channel_url'] = $validated['youtube_channel_url'] ?? null;
        $updates['submissions_open'] = (bool) $validated['submissions_open'];

        foreach (['avatar' => ['avatar_path', 'avatars'], 'hero' => ['hero_path', 'heroes']] as $input => [$column, $folder]) {
            if (($files[$input] ?? null) instanceof UploadedFile) {
                $updates[$column] = $this->store($files[$input], "creators/{$creator->id}/{$folder}", $input);
                $newPaths[] = $updates[$column];
                $assets[] = $input;
                if ($this->ownedPath($creator->{$column}, $creator)) {
                    $oldPaths[] = $creator->{$column};
                }
            } elseif (! empty($validated["remove_{$input}"])) {
                $updates[$column] = null;
                $assets[] = $input;
                if ($this->ownedPath($creator->{$column}, $creator)) {
                    $oldPaths[] = $creator->{$column};
                }
            }
        }

        try {
            DB::transaction(function () use ($creator, $updates, $validated): void {
                $creator->update($updates);
                if (array_key_exists('tags', $validated)) {
                    $wanted = $this->tags->resolve($creator, explode(',', (string) $validated['tags']))->pluck('id');
                    $creator->creatorTags()->whereNotIn('id', $wanted)->whereDoesntHave('recommendations')->delete();
                }
            });
        } catch (Throwable $e) {
            $this->disk()->delete($newPaths);
            throw $e;
        }

        $this->disk()->delete(array_unique($oldPaths));
        $creator->refresh();

        return ['before' => $before, 'after' => $creator->only($publicFields), 'assets' => $assets];
    }

    private function store(UploadedFile $file, string $directory, string $input): string
    {
        $temporaryPath = $file->getPathname();
        if ($temporaryPath === '' || ! is_readable($temporaryPath)) {
            throw ValidationException::withMessages([$input => 'The uploaded image could not be read.']);
        }
        $path = $directory.'/'.Str::uuid().'.'.$file->extension();
        $stream = fopen($temporaryPath, 'rb');
        try {
            $stored = $stream !== false && $this->disk()->put($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
        if (! $stored) {
            throw ValidationException::withMessages([$input => 'The uploaded image could not be saved.']);
        }

        return $path;
    }

    private function disk()
    {
        return Storage::disk(config('filesystems.default'));
    }

    private function ownedPath(?string $path, Creator $creator): bool
    {
        return filled($path) && ! filter_var($path, FILTER_VALIDATE_URL) && str_starts_with($path, "creators/{$creator->id}/");
    }
}
