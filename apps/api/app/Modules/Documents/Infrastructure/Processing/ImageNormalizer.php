<?php

declare(strict_types=1);

namespace App\Modules\Documents\Infrastructure\Processing;

/**
 * ImageNormalizer (Architecture §5.6 (8)).
 * Normalizes images to JPEG quality 85, maximum dimension 2400px.
 */
final class ImageNormalizer
{
    public const MAX_DIMENSION = 2400;

    public const JPEG_QUALITY = 85;

    /**
     * @return array{
     *     content: string,
     *     mime_type: string,
     *     width: int,
     *     height: int
     * }
     */
    public function normalize(string $content, string $mimeType): array
    {
        if ($mimeType === 'application/pdf') {
            return [
                'content' => $content,
                'mime_type' => 'application/pdf',
                'width' => 0,
                'height' => 0,
            ];
        }

        $info = @getimagesizefromstring($content);
        $width = $info !== false ? (int) $info[0] : 0;
        $height = $info !== false ? (int) $info[1] : 0;

        if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
            $img = @imagecreatefromstring($content);
            if ($img !== false) {
                $maxSide = max($width, $height);
                if ($maxSide > self::MAX_DIMENSION && $maxSide > 0) {
                    $scale = self::MAX_DIMENSION / $maxSide;
                    $newWidth = (int) round($width * $scale);
                    $newHeight = (int) round($height * $scale);
                    /** @var \GdImage|false $scaled */
                    $scaled = imagescale($img, $newWidth, $newHeight);
                    if ($scaled !== false) {
                        imagedestroy($img);
                        $img = $scaled;
                        $width = $newWidth;
                        $height = $newHeight;
                    }
                }

                ob_start();
                imagejpeg($img, null, self::JPEG_QUALITY);
                $cleanJpeg = ob_get_clean();
                imagedestroy($img);

                if (is_string($cleanJpeg) && $cleanJpeg !== '') {
                    return [
                        'content' => $cleanJpeg,
                        'mime_type' => 'image/jpeg',
                        'width' => $width,
                        'height' => $height,
                    ];
                }
            }
        }

        return [
            'content' => $content,
            'mime_type' => 'image/jpeg',
            'width' => $width,
            'height' => $height,
        ];
    }
}
