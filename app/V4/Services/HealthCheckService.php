<?php
declare(strict_types=1);
namespace GodyarV4\Services;

final class HealthCheckService
{
    public function checks(): array
    {
        $checks = [];

        $checks[] = $this->checkWritable('storage', godyar_v4_storage_path());
        $checks[] = $this->checkPath('theme layout', godyar_v4_theme_path('layout/master.php'));
        $checks[] = $this->checkPath('theme header', godyar_v4_theme_path('layout/header.php'));
        $checks[] = $this->checkPath('theme footer', godyar_v4_theme_path('layout/footer.php'));
        $checks[] = $this->checkPath('public v4 entry', godyar_v4_project_root() . '/public/index.v4.php');
        $checks[] = $this->checkDatabase();
        $checks[] = $this->checkRouteSnippet();

        return $checks;
    }

    public function summary(): array
    {
        $checks = $this->checks();
        $ok = count(array_filter($checks, static fn(array $check): bool => ($check['status'] ?? '') === 'ok'));
        $warn = count(array_filter($checks, static fn(array $check): bool => ($check['status'] ?? '') === 'warn'));
        $fail = count(array_filter($checks, static fn(array $check): bool => ($check['status'] ?? '') === 'fail'));

        return [
            'ok' => $ok,
            'warn' => $warn,
            'fail' => $fail,
            'generated_at' => date('c'),
            'checks' => $checks,
        ];
    }

    private function checkPath(string $label, string $path): array
    {
        $exists = is_file($path) || is_dir($path);
        return [
            'name' => $label,
            'status' => $exists ? 'ok' : 'fail',
            'message' => $exists ? 'المسار موجود' : 'المسار مفقود',
            'path' => $path,
        ];
    }

    private function checkWritable(string $label, string $path): array
    {
        $exists = is_dir($path);
        $writable = $exists && is_writable($path);
        return [
            'name' => $label,
            'status' => $writable ? 'ok' : ($exists ? 'warn' : 'fail'),
            'message' => $writable ? 'المجلد قابل للكتابة' : ($exists ? 'المجلد موجود لكن يحتاج صلاحيات' : 'المجلد غير موجود'),
            'path' => $path,
        ];
    }

    private function checkDatabase(): array
    {
        $pdo = godyar_v4_db();
        if (!$pdo) {
            return [
                'name' => 'database',
                'status' => 'warn',
                'message' => 'تعذر الوصول إلى قاعدة البيانات، سيتم استخدام fallback',
                'path' => '',
            ];
        }

        try {
            $pdo->query('SELECT 1');
            return [
                'name' => 'database',
                'status' => 'ok',
                'message' => 'الاتصال بقاعدة البيانات يعمل',
                'path' => '',
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'database',
                'status' => 'fail',
                'message' => 'فشل اختبار الاتصال: ' . $e->getMessage(),
                'path' => '',
            ];
        }
    }

    private function checkRouteSnippet(): array
    {
        $path = godyar_v4_project_root() . '/public/.htaccess.v4-snippet';
        $exists = is_file($path);
        return [
            'name' => 'rewrite snippet',
            'status' => $exists ? 'ok' : 'warn',
            'message' => $exists ? 'ملف snippet جاهز للدمج' : 'snippet غير موجود',
            'path' => $path,
        ];
    }
}
