<?php

namespace App\Support;

/**
 * Minimal sanitizer for administrator-authored page content.
 *
 * Keeps common formatting tags and removes scripts, inline event handlers and javascript: URLs.
 */
class Html
{
    private const ALLOWED_TAGS = '<p><br><b><strong><i><em><u><s><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><div><span><blockquote><table><thead><tbody><tr><th><td><hr><pre><code>';

    public static function sanitize(?string $html): string
    {
        $html = (string) $html;

        // Drop script/style blocks including their content
        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = strip_tags($html, self::ALLOWED_TAGS);

        // Inline event handlers: onclick="...", onerror='...', onload=...
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';

        // javascript:, vbscript: and data: URLs in href/src
        $html = preg_replace('/\b(href|src)\s*=\s*(["\'])\s*(?:javascript|vbscript|data):[^"\']*\2/i', '$1=$2#$2', $html) ?? '';

        // Legacy CSS script vectors
        $html = preg_replace('/expression\s*\(|url\s*\(\s*["\']?\s*javascript:/i', '', $html) ?? '';

        return trim($html);
    }
}
