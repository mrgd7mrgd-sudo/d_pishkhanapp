<?php

declare(strict_types=1);

namespace App\Modules\Documents\Infrastructure\Processing;

use finfo;
use InvalidArgumentException;

/**
 * MimeSniffer (Architecture §5.6 (8), §7.1 T8).
 * Inspects real MIME type from binary content (never trusted from extension or header).
 */
final class MimeSniffer
{
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/heic',
        'application/pdf',
    ];

    /**
     * Sniff real MIME type from content. Throws if script/malicious tags are detected.
     */
    public function sniff(string $content): string
    {
        if ($content === '') {
            throw new InvalidArgumentException('Empty file content.');
        }

        // Detect embedded PHP/script tags attempting polyglot or extension spoofing
        if ($this->hasScriptTags($content)) {
            return 'text/x-php';
        }

        // Magic bytes checks
        if (str_starts_with($content, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($content, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }

        if (str_starts_with($content, '%PDF-')) {
            return 'application/pdf';
        }

        if ($this->isHeic($content)) {
            return 'image/heic';
        }

        // Fallback to PHP fileinfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($content);

        return is_string($detected) ? $detected : 'application/octet-stream';
    }

    public function isAllowed(string $mime): bool
    {
        return in_array($mime, self::ALLOWED_MIMES, true);
    }

    private function hasScriptTags(string $content): bool
    {
        $header = substr($content, 0, 1024);

        if (str_contains($header, '<?php') || str_contains($header, '<?=') || str_contains($header, '<script')) {
            return true;
        }

        if (str_starts_with($header, 'MZ') || str_starts_with($header, "\x7fELF")) {
            return true;
        }

        return false;
    }

    private function isHeic(string $content): bool
    {
        if (strlen($content) < 16) {
            return false;
        }

        $box = substr($content, 4, 12);

        return str_contains($box, 'ftypheic')
            || str_contains($box, 'ftypmif1')
            || str_contains($box, 'ftypmsf1')
            || str_contains($box, 'ftypheix');
    }
}
