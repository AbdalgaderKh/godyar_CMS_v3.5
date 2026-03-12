<?php
declare(strict_types=1);
namespace GodyarV4\Services;

final class MediaOptimizerService
{
    public function capabilities(): array
    {
        $gd = extension_loaded('gd');
        $info = $gd ? gd_info() : [];
        return [
            'gd' => $gd,
            'webp' => $gd && !empty($info['WebP Support']),
            'avif' => $gd && !empty($info['AVIF Support']),
        ];
    }

    public function scan(int $limit = 120): array
    {
        $dirs = [
            godyar_v4_project_root() . '/uploads',
            godyar_v4_project_root() . '/uploads/media',
            godyar_v4_project_root() . '/assets/uploads',
            godyar_v4_project_root() . '/public/uploads',
        ];
        $rows = [];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (count($rows) >= $limit) {
                    break 2;
                }
                if (!$file->isFile()) {
                    continue;
                }
                $ext = strtolower($file->getExtension());
                if (!in_array($ext, ['jpg','jpeg','png'], true)) {
                    continue;
                }
                $path = $file->getPathname();
                $rows[] = [
                    'path' => $path,
                    'relative' => ltrim(str_replace(godyar_v4_project_root(), '', $path), '/'),
                    'size' => (int) $file->getSize(),
                    'webp_exists' => is_file((string) preg_replace('/\.(jpe?g|png)$/i', '.webp', $path)),
                    'avif_exists' => is_file((string) preg_replace('/\.(jpe?g|png)$/i', '.avif', $path)),
                ];
            }
        }
        return $rows;
    }

    public function optimize(string $relative): array
    {
        $src = godyar_v4_project_root() . '/' . ltrim($relative, '/');
        if (!is_file($src)) {
            return ['ok' => false, 'message' => 'الملف غير موجود.'];
        }
        $caps = $this->capabilities();
        if (!$caps['gd']) {
            return ['ok' => false, 'message' => 'GD غير متوفرة على الخادم.'];
        }
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $image = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($src),
            'png' => @imagecreatefrompng($src),
            default => false,
        };
        if (!$image) {
            return ['ok' => false, 'message' => 'تعذر فتح الصورة.'];
        }
        $generated = [];
        $base = (string) preg_replace('/\.(jpe?g|png)$/i', '', $src);
        if ($caps['webp']) {
            $webp = $base . '.webp';
            if (@imagewebp($image, $webp, 82)) {
                $generated[] = ltrim(str_replace(godyar_v4_project_root(), '', $webp), '/');
            }
        }
        if ($caps['avif'] && function_exists('imageavif')) {
            $avif = $base . '.avif';
            if (@imageavif($image, $avif, 60)) {
                $generated[] = ltrim(str_replace(godyar_v4_project_root(), '', $avif), '/');
            }
        }
        imagedestroy($image);
        return ['ok' => !empty($generated), 'message' => !empty($generated) ? 'تم توليد النسخ المحسنة.' : 'لم يتم توليد ملفات جديدة.', 'generated' => $generated];
    }
}
