@props([
    'video',
    'class' => 'w-24 rounded-lg',
])

<span
    data-creator-intelligence-thumbnail
    data-video-id="{{ $video->platform_video_id }}"
    data-title="{{ $video->title }}"
    data-thumbnail-url="{{ $video->thumbnail_url }}"
    data-large="{{ str_contains($class, 'w-full') ? 'true' : 'false' }}"
    class="relative flex aspect-video shrink-0 items-center justify-center overflow-hidden bg-slate-200 text-xs text-slate-500 dark:bg-slate-800 {{ $class }}"
>
    <span class="px-2 text-center">No image</span>
</span>
