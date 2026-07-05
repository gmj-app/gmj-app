<?php

namespace Tests\Unit;

use App\Services\Youtube\DescriptionPlanner;
use App\Services\Youtube\DescriptionUpdateOptions;
use App\Services\Youtube\YoutubeVideoSnippet;
use PHPUnit\Framework\TestCase;

class YoutubeDescriptionPlannerTest extends TestCase
{
    public function test_append_preview_preserves_existing_description_content(): void
    {
        $planner = new DescriptionPlanner;

        $preview = $planner->preview([
            $this->video(description: "Existing description\nLinks stay here."),
        ], new DescriptionUpdateOptions(
            appendText: 'Facebook: https://facebook.com/jfragment',
            appendOnlyIfMissing: true,
            addSeparator: true,
        ));

        $change = $preview->changedVideos()->first();

        $this->assertSame(1, $preview->totalVideos());
        $this->assertSame('changed', $change->status);
        $this->assertStringStartsWith("Existing description\nLinks stay here.", $change->newDescription);
        $this->assertStringContainsString("---\nFacebook: https://facebook.com/jfragment", $change->newDescription);
    }

    public function test_append_skips_when_text_already_exists(): void
    {
        $planner = new DescriptionPlanner;

        $preview = $planner->preview([
            $this->video(description: 'Facebook: https://facebook.com/jfragment'),
        ], new DescriptionUpdateOptions(
            appendText: 'Facebook: https://facebook.com/jfragment',
            appendOnlyIfMissing: true,
            addSeparator: true,
        ));

        $change = $preview->skippedVideos()->first();

        $this->assertSame('skipped', $change->status);
        $this->assertSame('Append text already exists.', $change->message);
        $this->assertSame($change->oldDescription, $change->newDescription);
    }

    public function test_replacement_uses_exact_find_text_only(): void
    {
        $planner = new DescriptionPlanner;

        $preview = $planner->preview([
            $this->video(description: 'Old URL: https://facebook.com/old-page'),
            $this->video(id: 'video-2', description: 'No matching link here.'),
        ], new DescriptionUpdateOptions(
            findText: 'https://facebook.com/old-page',
            replaceText: 'https://facebook.com/jfragment',
        ));

        $this->assertSame(1, $preview->changedVideos()->count());
        $this->assertSame(1, $preview->skippedVideos()->count());
        $this->assertStringContainsString('https://facebook.com/jfragment', $preview->changedVideos()->first()->newDescription);
    }

    public function test_description_length_limit_skips_oversized_updates(): void
    {
        $planner = new DescriptionPlanner;

        $preview = $planner->preview([
            $this->video(description: str_repeat('a', 4998)),
        ], new DescriptionUpdateOptions(
            appendText: 'Facebook: https://facebook.com/jfragment',
            appendOnlyIfMissing: true,
            addSeparator: true,
        ));

        $change = $preview->skippedVideos()->first();

        $this->assertSame('skipped', $change->status);
        $this->assertSame('Updated description would exceed YouTube length limits.', $change->message);
    }

    private function video(string $id = 'video-1', string $description = ''): YoutubeVideoSnippet
    {
        return new YoutubeVideoSnippet($id, [
            'title' => 'Video title',
            'description' => $description,
            'categoryId' => '10',
            'tags' => ['keep-me'],
        ]);
    }
}
