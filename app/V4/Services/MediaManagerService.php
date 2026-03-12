<?php
declare(strict_types=1);
namespace GodyarV4\Services;

final class MediaManagerService
{
    public function storeUploadedFile(array $file, string $subdir = 'media'): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'فشل رفع الملف أو لم يتم اختيار ملف.'];
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'message' => 'ملف الرفع غير صالح.'];
        }

        $original = (string)($file['name'] ?? 'upload');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (!in_array($ext, $allowed, true)) {
            return ['ok' => false, 'message' => 'نوع الملف غير مدعوم.'];
        }

        $relativeDir = 'uploads/' . trim($subdir, '/');
        $absoluteDir = godyar_v4_project_root() . '/' . $relativeDir;
        if (!is_dir($absoluteDir) && !@mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            return ['ok' => false, 'message' => 'تعذر إنشاء مجلد الوسائط.'];
        }

        $base = godyar_v4_slugify(pathinfo($original, PATHINFO_FILENAME));
        $targetName = $base . '-' . date('YmdHis') . '.' . $ext;
        $absolute = $absoluteDir . '/' . $targetName;
        $relative = $relativeDir . '/' . $targetName;
        if (!@move_uploaded_file($tmp, $absolute)) {
            return ['ok' => false, 'message' => 'تعذر نقل الملف المرفوع.'];
        }

        $webp = $this->createWebpVariant($absolute, $relative);
        $thumb = $this->createResizedVariant($absolute, $relative, 640, 360, 'thumb');

        return [
            'ok' => true,
            'path' => $relative,
            'url' => godyar_v4_media_url($relative),
            'webp_path' => $webp['path'] ?? null,
            'webp_url' => $webp['url'] ?? null,
            'thumb_path' => $thumb['path'] ?? null,
            'thumb_url' => $thumb['url'] ?? null,
            'message' => 'تم رفع الملف بنجاح.',
        ];
    }

    private function createWebpVariant(string $absolute, string $relative): array
    {
        if (!extension_loaded('gd')) {
            return ['ok' => false];
        }
        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return ['ok' => false];
        }
        $image = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($absolute),
            'png' => @imagecreatefrompng($absolute),
            default => false,
        };
        if (!$image) {
            return ['ok' => false];
        }
        imagesavealpha($image, true);
        $webpAbs = preg_replace('/\.(jpe?g|png)$/i', '.webp', $absolute);
        $webpRel = preg_replace('/\.(jpe?g|png)$/i', '.webp', $relative);
        if (!is_string($webpAbs) || !is_string($webpRel)) {
            imagedestroy($image);
            return ['ok' => false];
        }
        $ok = @imagewebp($image, $webpAbs, 82);
        imagedestroy($image);
        return $ok ? ['ok' => true, 'path' => $webpRel, 'url' => godyar_v4_media_url($webpRel)] : ['ok' => false];
    }

    private function createResizedVariant(string $absolute, string $relative, int $targetW, int $targetH, string $suffix): array
    {
        if (!extension_loaded('gd')) {
            return ['ok' => false];
        }
        $info = @getimagesize($absolute);
        if (!$info || empty($info[0]) || empty($info[1])) {
            return ['ok' => false];
        }
        [$width, $height] = $info;
        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return ['ok' => false];
        }
        $src = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($absolute),
            'png' => @imagecreatefrompng($absolute),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolute) : false,
            default => false,
        };
        if (!$src) {
            return ['ok' => false];
        }
        $scale = min($targetW / $width, $targetH / $height, 1);
        $newW = max(1, (int) floor($width * $scale));
        $newH = max(1, (int) floor($height * $scale));
        $canvas = imagecreatetruecolor($newW, $newH);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
        $targetAbs = preg_replace('/(\.[^.]+)$/', '-' . $suffix . '$1', $absolute);
        $targetRel = preg_replace('/(\.[^.]+)$/', '-' . $suffix . '$1', $relative);
        $ok = false;
        if (is_string($targetAbs) && is_string($targetRel)) {
            $ok = match ($ext) {
                'jpg', 'jpeg' => @imagejpeg($canvas, $targetAbs, 86),
                'png' => @imagepng($canvas, $targetAbs, 6),
                'webp' => function_exists('imagewebp') ? @imagewebp($canvas, $targetAbs, 82) : false,
                default => false,
            };
        }
        imagedestroy($src);
        imagedestroy($canvas);
        return $ok && is_string($targetRel) ? ['ok' => true, 'path' => $targetRel, 'url' => godyar_v4_media_url($targetRel)] : ['ok' => false];
    }
}
