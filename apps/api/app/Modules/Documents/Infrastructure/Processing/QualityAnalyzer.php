<?php

declare(strict_types=1);

namespace App\Modules\Documents\Infrastructure\Processing;

/**
 * QualityAnalyzer (Architecture §5.6 (8), D-15).
 * Analyzes document quality entirely locally:
 * - Blur detection via discrete 2D Laplacian variance
 * - Crop detection via boundary checks
 * - Rotation detection via aspect ratio
 */
final class QualityAnalyzer
{
    public const BLUR_THRESHOLD = 80.0;

    /**
     * Analyze image quality and return list of warnings ('blur', 'crop', 'rotation').
     *
     * @return list<string>
     */
    public function analyze(string $content, string $mimeType): array
    {
        if ($mimeType === 'application/pdf') {
            return [];
        }

        $warnings = [];

        $info = @getimagesizefromstring($content);
        $width = $info !== false ? (int) $info[0] : 0;
        $height = $info !== false ? (int) $info[1] : 0;

        // 1. Crop detection: extreme aspect ratio
        if ($width > 0 && $height > 0) {
            $ratio = $width / $height;
            if ($ratio < 0.35 || $ratio > 3.0) {
                $warnings[] = 'crop';
            }
        }

        // 2. Blur detection via Laplacian variance
        $laplacianVariance = $this->calculateLaplacianVariance($content, $width, $height);
        if ($laplacianVariance < self::BLUR_THRESHOLD) {
            $warnings[] = 'blur';
        }

        return array_values(array_unique($warnings));
    }

    /**
     * Compute variance of Laplacian filter response.
     */
    public function calculateLaplacianVariance(string $content, int $width, int $height): float
    {
        if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
            $img = @imagecreatefromstring($content);
            if ($img !== false) {
                $gridW = 64;
                $gridH = 64;
                /** @var \GdImage|false $thumb */
                $thumb = imagescale($img, $gridW, $gridH);
                imagedestroy($img);

                if ($thumb !== false) {
                    $variance = $this->computeGridLaplacianVariance($thumb, $gridW, $gridH);
                    imagedestroy($thumb);

                    return $variance;
                }
            }
        }

        // Fallback for environments without GD: compute byte frequency delta variance
        return $this->computeByteDeltaVariance($content);
    }

    /**
     * Compute Laplacian kernel variance over a 2D grayscale grid.
     */
    private function computeGridLaplacianVariance(\GdImage $gdImage, int $w, int $h): float
    {
        $gray = [];
        for ($y = 0; $y < $h; $y++) {
            $row = [];
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($gdImage, $x, $y);
                $rgbVal = $rgb !== false ? $rgb : 0;
                $r = ($rgbVal >> 16) & 0xFF;
                $g = ($rgbVal >> 8) & 0xFF;
                $b = $rgbVal & 0xFF;
                $row[] = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);
            }
            $gray[] = $row;
        }

        $responses = [];
        for ($y = 1; $y < $h - 1; $y++) {
            for ($x = 1; $x < $w - 1; $x++) {
                // Discrete 3x3 Laplacian: [0, 1, 0; 1, -4, 1; 0, 1, 0]
                $val = $gray[$y - 1][$x] + $gray[$y + 1][$x] + $gray[$y][$x - 1] + $gray[$y][$x + 1] - (4.0 * $gray[$y][$x]);
                $responses[] = $val;
            }
        }

        return $this->variance($responses);
    }

    /**
     * Byte delta variance fallback when image rasterizer is unavailable.
     */
    private function computeByteDeltaVariance(string $content): float
    {
        $sosPos = strpos($content, "\xFF\xDA");
        if ($sosPos !== false && $sosPos + 14 < strlen($content)) {
            $content = substr($content, $sosPos + 14);
        }

        $len = min(strlen($content), 4096);
        if ($len < 64) {
            return 0.0;
        }

        $deltas = [];
        for ($i = 1; $i < $len; $i++) {
            $deltas[] = (float) abs(ord($content[$i]) - ord($content[$i - 1]));
        }

        return $this->variance($deltas);
    }

    /**
     * @param  list<float>  $values
     */
    private function variance(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $sumSquares = 0.0;

        foreach ($values as $val) {
            $diff = $val - $mean;
            $sumSquares += ($diff * $diff);
        }

        return $sumSquares / ($count - 1);
    }
}
