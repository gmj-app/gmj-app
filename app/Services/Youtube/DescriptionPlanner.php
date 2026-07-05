<?php

namespace App\Services\Youtube;

class DescriptionPlanner
{
    public const MAX_DESCRIPTION_LENGTH = 5000;

    /**
     * @param  iterable<YoutubeVideoSnippet>  $videos
     */
    public function preview(iterable $videos, DescriptionUpdateOptions $options): DescriptionPreview
    {
        return new DescriptionPreview(collect($videos)
            ->map(fn (YoutubeVideoSnippet $video) => $this->planForVideo($video, $options)));
    }

    public function planForVideo(YoutubeVideoSnippet $video, DescriptionUpdateOptions $options): DescriptionChange
    {
        $oldDescription = $video->description();

        if ($options->isReplacement()) {
            return $this->replacementChange($video, $options, $oldDescription);
        }

        return $this->appendChange($video, $options, $oldDescription);
    }

    private function replacementChange(
        YoutubeVideoSnippet $video,
        DescriptionUpdateOptions $options,
        string $oldDescription,
    ): DescriptionChange {
        $findText = (string) $options->findText;
        $replaceText = (string) $options->replaceText;

        if (! str_contains($oldDescription, $findText)) {
            return new DescriptionChange(
                videoId: $video->id,
                videoTitle: $video->title(),
                oldDescription: $oldDescription,
                newDescription: $oldDescription,
                action: 'replace',
                status: 'skipped',
                message: 'Find text was not present.',
            );
        }

        return $this->finalizeChange($video, 'replace', $oldDescription, str_replace($findText, $replaceText, $oldDescription));
    }

    private function appendChange(
        YoutubeVideoSnippet $video,
        DescriptionUpdateOptions $options,
        string $oldDescription,
    ): DescriptionChange {
        $appendText = trim($options->appendText);

        if ($appendText === '') {
            return new DescriptionChange(
                videoId: $video->id,
                videoTitle: $video->title(),
                oldDescription: $oldDescription,
                newDescription: $oldDescription,
                action: 'append',
                status: 'skipped',
                message: 'No append text was provided.',
            );
        }

        if ($options->appendOnlyIfMissing && str_contains($oldDescription, $appendText)) {
            return new DescriptionChange(
                videoId: $video->id,
                videoTitle: $video->title(),
                oldDescription: $oldDescription,
                newDescription: $oldDescription,
                action: 'append',
                status: 'skipped',
                message: 'Append text already exists.',
            );
        }

        $separator = $options->addSeparator ? "\n\n---\n" : "\n\n";
        $newDescription = rtrim($oldDescription).$separator.$appendText;

        return $this->finalizeChange($video, 'append', $oldDescription, $newDescription);
    }

    private function finalizeChange(
        YoutubeVideoSnippet $video,
        string $action,
        string $oldDescription,
        string $newDescription,
    ): DescriptionChange {
        if (mb_strlen($newDescription) > self::MAX_DESCRIPTION_LENGTH) {
            return new DescriptionChange(
                videoId: $video->id,
                videoTitle: $video->title(),
                oldDescription: $oldDescription,
                newDescription: $oldDescription,
                action: $action,
                status: 'skipped',
                message: 'Updated description would exceed YouTube length limits.',
            );
        }

        if ($newDescription === $oldDescription) {
            return new DescriptionChange(
                videoId: $video->id,
                videoTitle: $video->title(),
                oldDescription: $oldDescription,
                newDescription: $oldDescription,
                action: $action,
                status: 'skipped',
                message: 'No description change was needed.',
            );
        }

        return new DescriptionChange(
            videoId: $video->id,
            videoTitle: $video->title(),
            oldDescription: $oldDescription,
            newDescription: $newDescription,
            action: $action,
            status: 'changed',
        );
    }
}
