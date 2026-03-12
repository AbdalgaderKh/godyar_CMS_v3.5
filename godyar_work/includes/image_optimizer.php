<?php

declare(strict_types=1);

if (!function_exists('gdy_convert_to_webp')) {
    function gdy_convert_to_webp(string $source, int $quality = 80): ?string
    {
        if (!is_file($source) || !function_exists('getimagesize')) {
            return null;
        }

        $info = @getimagesize($source);
        if (!$info || empty($info['mime'])) {
            return null;
        }

        $image = null;

        switch ($info['mime']) {
            case 'image/jpeg':
                if (!function_exists('imagecreatefromjpeg')) {
                    return null;
                }
                $image = @imagecreatefromjpeg($source);
                break;

            case 'image/png':
                if (!function_exists('imagecreatefrompng')) {
                    return null;
                }
                $image = @imagecreatefrompng($source);
                if ($image && function_exists('imagepalettetotruecolor')) {
                    @imagepalettetotruecolor($image);
                }
                if ($image) {
                    @imagealphablending($image, true);
                    @imagesavealpha($image, true);
                }
                break;

            default:
                return null;
        }

        if (!$image || !function_exists('imagewebp')) {
            return null;
        }

        $webp = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source);
        if (!$webp) {
            @imagedestroy($image);
            return null;
        }

        $ok = @imagewebp($image, $webp, max(10, min(100, $quality)));
        @imagedestroy($image);

        return $ok ? $webp : null;
    }
}