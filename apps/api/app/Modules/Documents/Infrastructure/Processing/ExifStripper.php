<?php

declare(strict_types=1);

namespace App\Modules\Documents\Infrastructure\Processing;

/**
 * ExifStripper (Architecture §5.6 (8), §7.1 T8).
 * Strips all EXIF, GPS coordinates, and metadata markers from images to prevent PII leakage.
 */
final class ExifStripper
{
    /**
     * Strip all EXIF and metadata markers from image binary.
     */
    public function strip(string $content, string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => $this->stripJpeg($content),
            'image/png' => $this->stripPng($content),
            default => $content,
        };
    }

    /**
     * Check whether binary contains EXIF markers.
     */
    public function hasExif(string $content): bool
    {
        if (str_starts_with($content, "\xFF\xD8")) {
            return str_contains($content, "\xFF\xE1");
        }

        if (str_starts_with($content, "\x89PNG\r\n\x1a\n")) {
            return str_contains($content, 'eXIf') || str_contains($content, 'tEXt');
        }

        return false;
    }

    /**
     * Strips APP1 (EXIF/XMP/GPS) and comment markers from JPEG stream.
     */
    private function stripJpeg(string $data): string
    {
        if (! str_starts_with($data, "\xFF\xD8")) {
            return $data;
        }

        $length = strlen($data);
        $output = "\xFF\xD8";
        $pos = 2;

        while ($pos < $length) {
            if ($data[$pos] !== "\xFF") {
                break;
            }

            // Skip fill bytes
            while ($pos < $length && $data[$pos] === "\xFF") {
                $pos++;
            }

            if ($pos >= $length) {
                break;
            }

            $marker = ord($data[$pos]);
            $pos++;

            // Standalone markers (SOI, EOI, RST0..RST7, TEM)
            if ($marker === 0xD9) { // EOI
                $output .= "\xFF\xD9";
                break;
            }

            if ($marker === 0x00 || ($marker >= 0xD0 && $marker <= 0xD7)) {
                $output .= "\xFF".chr($marker);

                continue;
            }

            if ($pos + 2 > $length) {
                break;
            }

            $segLength = (ord($data[$pos]) << 8) + ord($data[$pos + 1]);
            $segData = substr($data, $pos + 2, $segLength - 2);

            // Skip APP1 (0xE1: Exif/GPS), APP2..APP15 (0xE2..0xEF), COM (0xFE)
            $isMetadataMarker = ($marker >= 0xE1 && $marker <= 0xEF) || ($marker === 0xFE);

            if (! $isMetadataMarker) {
                $output .= "\xFF".chr($marker).substr($data, $pos, $segLength);
            }

            $pos += $segLength;

            // When Start of Scan (SOS 0xDA) is reached, remainder is entropy-coded image data
            if ($marker === 0xDA) {
                $output .= substr($data, $pos);
                break;
            }
        }

        return $output;
    }

    /**
     * Strips eXIf, tEXt, zTXt, iTXt chunks from PNG.
     */
    private function stripPng(string $data): string
    {
        $sig = "\x89PNG\r\n\x1a\n";
        if (! str_starts_with($data, $sig)) {
            return $data;
        }

        $pos = 8;
        $length = strlen($data);
        $output = $sig;

        while ($pos + 8 <= $length) {
            $unpacked = unpack('N', substr($data, $pos, 4));
            $chunkLen = is_array($unpacked) && isset($unpacked[1]) ? (int) $unpacked[1] : 0;
            $chunkType = substr($data, $pos + 4, 4);
            $totalLen = 4 + 4 + $chunkLen + 4; // length + type + data + crc

            if ($pos + $totalLen > $length) {
                break;
            }

            // Exclude EXIF and textual metadata chunks
            if (! in_array($chunkType, ['eXIf', 'tEXt', 'zTXt', 'iTXt'], true)) {
                $output .= substr($data, $pos, $totalLen);
            }

            $pos += $totalLen;

            if ($chunkType === 'IEND') {
                break;
            }
        }

        return $output;
    }
}
