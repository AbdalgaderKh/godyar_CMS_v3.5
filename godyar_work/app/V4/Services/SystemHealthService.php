<?php
declare(strict_types=1);
namespace GodyarV4\Services;

final class SystemHealthService
{
    public function report(): array
    {
        $checks = [];
        $pdo = godyar_v4_db();
        $checks[] = ['label'=>'Database','status'=>$pdo ? 'ok' : 'warn','message'=>$pdo ? 'الاتصال بقاعدة البيانات متاح' : 'لا يوجد اتصال نشط بقاعدة البيانات'];
        $checks[] = ['label'=>'PHP','status'=>version_compare(PHP_VERSION, '8.0', '>=') ? 'ok' : 'warn','message'=>'الإصدار: '.PHP_VERSION];
        $checks[] = ['label'=>'GD Library','status'=>extension_loaded('gd') ? 'ok' : 'warn','message'=>extension_loaded('gd') ? 'مكتبة GD مفعلة' : 'مكتبة GD غير مفعلة'];
        $checks[] = ['label'=>'Upload Max','status'=>'ok','message'=>'upload_max_filesize='.(string)ini_get('upload_max_filesize')];
        foreach ([godyar_v4_storage_path(), godyar_v4_project_root().'/uploads', godyar_v4_project_root().'/themes', godyar_v4_project_root().'/public'] as $path) {
            $checks[] = ['label'=>'Writable: '.basename($path),'status'=>is_dir($path) && is_writable($path) ? 'ok' : 'warn','message'=>$path];
        }
        $cacheDir = godyar_v4_storage_path('cache');
        $checks[] = ['label'=>'Cache Directory','status'=>is_dir($cacheDir) ? 'ok' : 'warn','message'=>$cacheDir];
        $broken = [
            'legacy_redirects' => count((array)godyar_v4_config('legacy_redirects')),
            'trash_files' => $this->countFiles(godyar_v4_storage_path('trash/media')),
            'log_files' => $this->countFiles(godyar_v4_storage_path('reports')),
        ];
        return ['checks'=>$checks,'summary'=>$broken];
    }
    private function countFiles(string $dir): int
    {
        if (!is_dir($dir)) return 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        $count = 0; foreach ($it as $file) { if ($file->isFile()) $count++; }
        return $count;
    }
}
