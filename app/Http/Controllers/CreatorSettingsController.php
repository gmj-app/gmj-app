<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CreatorSettingsController extends Controller
{
    public function edit(Request $request, Creator $creator): View
    {
        Gate::authorize('manage', $creator);

        return view('creators.settings', compact('creator'));
    }

    public function update(Request $request, Creator $creator): RedirectResponse
    {
        Gate::authorize('manage', $creator);

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('creators', 'slug')->ignore($creator->id),
            ],
            'youtube_channel_url' => ['nullable', 'url', 'max:2048'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'submission_instructions' => ['nullable', 'string', 'max:2000'],
            'submissions_open' => ['required', 'boolean'],
            'recommendation_approval_mode' => [
                'required',
                Rule::in(Creator::RECOMMENDATION_APPROVAL_MODES),
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hero' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_avatar' => ['nullable', 'boolean'],
            'remove_hero' => ['nullable', 'boolean'],
        ]);

        $updates = [
            ...$validated,
            'channel_url' => $validated['youtube_channel_url'] ?? null,
            'submissions_open' => $request->boolean('submissions_open'),
        ];

        unset(
            $updates['avatar'],
            $updates['hero'],
            $updates['remove_avatar'],
            $updates['remove_hero'],
        );

        $newPaths = [];
        $oldPaths = [];

        foreach ([
            'avatar' => ['column' => 'avatar_path', 'directory' => "creators/{$creator->id}/avatars"],
            'hero' => ['column' => 'hero_path', 'directory' => "creators/{$creator->id}/heroes"],
        ] as $input => $branding) {
            $column = $branding['column'];

            if ($request->hasFile($input)) {
                $path = $this->storeBrandingUpload(
                    $request->file($input),
                    $branding['directory'],
                    $input,
                );

                $updates[$column] = $path;
                $newPaths[] = $path;

                if (
                    filled($creator->{$column})
                    && $this->isStoredCreatorBrandingPath($creator->{$column}, $creator)
                ) {
                    $oldPaths[] = $creator->{$column};
                }
            } elseif ($request->boolean("remove_{$input}")) {
                $updates[$column] = null;

                if (
                    filled($creator->{$column})
                    && $this->isStoredCreatorBrandingPath($creator->{$column}, $creator)
                ) {
                    $oldPaths[] = $creator->{$column};
                }
            }
        }

        try {
            $creator->update($updates);
        } catch (Throwable $exception) {
            $this->brandingDisk()->delete($newPaths);

            throw $exception;
        }

        $this->brandingDisk()->delete(array_unique($oldPaths));

        return redirect()
            ->route('creators.settings.edit', $creator)
            ->with('success', 'Creator settings updated.');
    }

    public function deactivate(Request $request, Creator $creator): RedirectResponse
    {
        Gate::authorize('manage', $creator);

        $creator->update([
            'status' => 'inactive',
            'deactivated_at' => now(),
            'submissions_open' => false,
        ]);

        return redirect()
            ->route('creators.dashboard', $creator)
            ->with('success', 'Creator page deactivated.');
    }

    private function storeBrandingUpload(UploadedFile $file, string $directory, string $input): string
    {
        $temporaryPath = $file->getPathname();

        if ($temporaryPath === '' || ! is_file($temporaryPath) || ! is_readable($temporaryPath)) {
            throw ValidationException::withMessages([
                $input => 'The uploaded image could not be read. Please choose the file again and retry.',
            ]);
        }

        $extension = $file->extension();
        $path = trim($directory, '/').'/'.Str::uuid().($extension ? ".{$extension}" : '');
        $stream = fopen($temporaryPath, 'rb');

        if ($stream === false) {
            throw ValidationException::withMessages([
                $input => 'The uploaded image could not be read. Please choose the file again and retry.',
            ]);
        }

        try {
            $stored = $this->brandingDisk()->put($path, $stream);
        } finally {
            fclose($stream);
        }

        if (! $stored) {
            throw ValidationException::withMessages([
                $input => 'The uploaded image could not be saved. Please retry.',
            ]);
        }

        return $path;
    }

    private function brandingDisk()
    {
        return Storage::disk(config('filesystems.default'));
    }

    private function isStoredCreatorBrandingPath(string $path, Creator $creator): bool
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return false;
        }

        return str_starts_with($path, "creators/{$creator->id}/")
            || str_starts_with($path, "creator-branding/{$creator->id}/");
    }
}
