@props(['text' => null])

@php
    use Illuminate\Support\HtmlString;

    $source = (string) ($text ?? '');
    $urlPattern = '~https?://[^\s<>"\']+~i';
    $linkClasses = 'font-semibold text-indigo-600 underline-offset-2 break-words hover:underline [overflow-wrap:anywhere] dark:text-indigo-300';

    $html = '';
    $offset = 0;

    preg_match_all($urlPattern, $source, $matches, PREG_OFFSET_CAPTURE);

    foreach ($matches[0] as [$matchedUrl, $position]) {
        $html .= e(substr($source, $offset, $position - $offset));

        $url = $matchedUrl;
        $trailing = '';

        while ($url !== '' && preg_match('/[.,!?;:)]\z/', $url) === 1) {
            $trailing = substr($url, -1).$trailing;
            $url = substr($url, 0, -1);
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if ($host && in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            $escapedUrl = e($url);
            $html .= sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer nofollow ugc" class="%s">%s</a>',
                $escapedUrl,
                e($linkClasses),
                $escapedUrl,
            );
        } else {
            $html .= e($matchedUrl);
        }

        $html .= e($trailing);
        $offset = $position + strlen($matchedUrl);
    }

    $html .= e(substr($source, $offset));
@endphp

{!! new HtmlString($html) !!}
