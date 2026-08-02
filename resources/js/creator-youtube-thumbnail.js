const youtubeHosts = new Set(['i.ytimg.com', 'img.youtube.com']);

export const youtubeThumbnailVariants = (videoId) => [
    `https://i.ytimg.com/vi/${videoId}/maxresdefault.jpg`,
    `https://i.ytimg.com/vi/${videoId}/sddefault.jpg`,
    `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`,
    `https://i.ytimg.com/vi/${videoId}/mqdefault.jpg`,
];

const isCustomUrl = (value) => {
    if (!value) return false;
    try {
        return !youtubeHosts.has(new URL(value, window.location.origin).hostname.toLowerCase());
    } catch {
        return false;
    }
};

export const mountYoutubeThumbnail = (container, { videoId, title, customUrl = null, large = false }) => {
    const variants = youtubeThumbnailVariants(videoId);
    const sources = isCustomUrl(customUrl) ? [customUrl, ...variants] : variants;
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
    root.querySelectorAll('table tbody tr').forEach((row) => {
        const cells = row.querySelectorAll(':scope > td');
        if (cells.length < 2) return;
        const id = cells[1].querySelector('span.text-xs')?.textContent?.trim();
        if (!/^[A-Za-z0-9_-]{11}$/.test(id || '')) return;
        const title = cells[1].querySelector('a')?.textContent?.trim() || 'video';
        const existing = cells[0].querySelector('img')?.getAttribute('src');
        mountYoutubeThumbnail(cells[0], { videoId: id, title, customUrl: existing });
    });

    root.querySelectorAll('img[src*="i.ytimg.com/vi/"], img[src*="img.youtube.com/vi/"]').forEach((image) => {
        if (image.parentElement?.dataset.youtubeThumbnail) return;
        const id = image.src.match(/\/vi\/([A-Za-z0-9_-]{11})\//)?.[1];
        if (!id) return;
        mountYoutubeThumbnail(image.parentElement, { videoId: id, title: image.alt.replace(/^Thumbnail for /, ''), customUrl: image.src, large: image.classList.contains('w-full') });
    });
};
