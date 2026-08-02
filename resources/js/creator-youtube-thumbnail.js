export const youtubeThumbnailVariants = (videoId) => [
    `https://i.ytimg.com/vi/${videoId}/maxresdefault.jpg`,
    `https://i.ytimg.com/vi/${videoId}/sddefault.jpg`,
    `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`,
    `https://i.ytimg.com/vi/${videoId}/mqdefault.jpg`,
];

export const mountYoutubeThumbnail = (container, { videoId, title, customUrl = null, large = false }) => {
    const variants = youtubeThumbnailVariants(videoId);
    const sources = [...new Set([customUrl, ...variants].filter(Boolean))];
    let index = 0;

    container.replaceChildren();
    container.dataset.youtubeThumbnail = videoId;
    container.dataset.thumbnailVariants = variants.join('|');

    const placeholder = () => {
        const fallback = document.createElement('span');
        fallback.className = `flex aspect-video ${large ? 'w-full' : 'w-24'} items-center justify-center rounded-lg bg-slate-200 text-xs text-slate-500 dark:bg-slate-800`;
        fallback.textContent = 'No image';
        fallback.dataset.thumbnailUnavailable = 'true';
        container.replaceChildren(fallback);
    };
    const attempt = () => {
        if (index >= sources.length) {
            placeholder();
            return;
        }
        const image = document.createElement('img');
        image.src = sources[index++];
        image.alt = `Thumbnail for ${title}`;
        image.loading = 'lazy';
        image.className = `aspect-video ${large ? 'w-full rounded-xl' : 'w-24 rounded-lg'} object-cover`;
        image.addEventListener('error', attempt, { once: true });
        image.addEventListener('load', () => {
            // Missing YouTube variants commonly return a 120x90 gray placeholder with HTTP 200.
            if (image.naturalWidth <= 120 && image.naturalHeight <= 90) attempt();
        }, { once: true });
        container.replaceChildren(image);
    };
    attempt();
};

export const enhanceCreatorYoutubeThumbnails = (root = document) => {
    root.querySelectorAll('[data-creator-intelligence-thumbnail]').forEach((container) => {
        const videoId = container.dataset.videoId?.trim();
        if (!/^[A-Za-z0-9_-]{11}$/.test(videoId || '')) return;
        mountYoutubeThumbnail(container, {
            videoId,
            title: container.dataset.title || 'video',
            customUrl: container.dataset.thumbnailUrl || null,
            large: container.dataset.large === 'true',
        });
    });
};
