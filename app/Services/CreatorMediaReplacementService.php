<?php

namespace App\Services;

use App\Models\Creator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreatorMediaReplacementService
{
    /** @return array{updates:array<string,string>,new_paths:array<int,string>,old_paths:array<int,string>,assets:array<int,string>} */
    public function stage(Creator $creator, array $files): array
    {
        $updates = $newPaths = $oldPaths = $assets = [];

        try {
            foreach (['avatar' => ['avatar_path', 'avatars'], 'hero' => ['hero_path', 'heroes']] as $input => [$column, $folder]) {
                if (! (($files[$input] ?? null) instanceof UploadedFile)) {
                    continue;
                }

                $updates[$column] = $this->store($files[$input], "creators/{$creator->id}/{$folder}", $input);
                $newPaths[] = $updates[$column];
                $assets[] = $input;
                if ($this->ownedPath($creator->{$column}, $creator)) {
                    $oldPaths[] = $creator->{$column};
                }
            }
        } catch (Throwable $exception) {
            $this->delete($newPaths);
            throw $exception;
        }

        return compact('updates', 'newPaths', 'oldPaths', 'assets');
    }

    /** @param array<int,string> $paths */
    public function delete(array $paths): void
    {
        if ($paths !== []) {
            Storage::disk('creator_uploads')->delete(array_unique($paths));
        }
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
            $stored = $stream !== false && Storage::disk('creator_uploads')->put($path, $stream);
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

    private function ownedPath(?string $path, Creator $creator): bool
    {
        return filled($path) && ! filter_var($path, FILTER_VALIDATE_URL) && str_starts_with($path, "creators/{$creator->id}/");
    }
}
