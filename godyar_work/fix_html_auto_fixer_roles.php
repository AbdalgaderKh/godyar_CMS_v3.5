<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);

$ROOT = realpath(__DIR__) ?: __DIR__;
$RUN = (($_GET['run'] ?? '') === '1');

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function out(string $msg, string $type = 'info'): void {
    $colors = [
        'info' => '#0f172a',
        'ok'   => '#166534',
        'warn' => '#92400e',
        'err'  => '#991b1b',
    ];
    echo '<div style="padding:8px 12px;border-bottom:1px solid #e5e7eb;color:' . $colors[$type] . ';">' . h($msg) . '</div>';
    @ob_flush();
    @flush();
}

function ensure_dir(string $dir): bool {
    return is_dir($dir) || @mkdir($dir, 0755, true);
}

function safe_get(string $file): string {
    $c = @file_get_contents($file);
    return is_string($c) ? $c : '';
}

function safe_put(string $file, string $content): bool {
    return file_put_contents($file, $content) !== false;
}

function rel_path(string $path, string $root): string {
    $path = str_replace('\\', '/', $path);
    $root = rtrim(str_replace('\\', '/', $root), '/');
    return ltrim(str_replace($root, '', $path), '/');
}

function backup_file(string $file, string $root): void {
    if (!is_file($file)) return;
    $dir = $root . '/_html_role_fix_backups/' . date('Ymd_His');
    ensure_dir($dir);
    @copy($file, $dir . '/' . str_replace('/', '__', rel_path($file, $root)));
}

function find_files(string $root): array {
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    $files = [];
    foreach ($rii as $file) {
        /** @var SplFileInfo $file */
        if (!$file->isFile()) continue;

        $path = $file->getPathname();
        $rel = rel_path($path, $root);

        if (preg_match('~(^|/)(vendor|node_modules|\.git|storage|cache|logs|uploads|_html_auto_fixer_backups|_html_role_fix_backups|1|backups?)(/|$)~i', $rel)) {
            continue;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['php', 'html'], true)) continue;

        $files[] = $path;
    }

    sort($files);
    return $files;
}

/**
 * يترك أول role فقط ويحذف المكرر
 */
function dedupe_role_attributes(string $html, int &$count = 0): string {
    return preg_replace_callback(
        '~<([a-zA-Z][a-zA-Z0-9:-]*)([^>]*)>~s',
        function ($m) use (&$count) {
            $tag = $m[1];
            $attrs = $m[2];

            if (!preg_match_all('/\srole\s*=\s*("|\')(.*?)\1/is', $attrs, $roles, PREG_SET_ORDER)) {
                return '<' . $tag . $attrs . '>';
            }

            if (count($roles) <= 1) {
                return '<' . $tag . $attrs . '>';
            }

            $firstRole = trim((string)$roles[0][2]);

            $attrsWithoutRoles = preg_replace('/\srole\s*=\s*("|\')(.*?)\1/is', '', $attrs);
            $attrsWithoutRoles = preg_replace('/\s+/', ' ', $attrsWithoutRoles);
            $attrsWithoutRoles = rtrim($attrsWithoutRoles);

            $count++;
            return '<' . $tag . $attrsWithoutRoles . ' role="' . htmlspecialchars($firstRole, ENT_QUOTES, 'UTF-8') . '">';
        },
        $html
    );
}

/**
 * يزيل role="region" من div إذا كان لديها role آخر أصلًا
 */
function remove_redundant_region_role(string $html, int &$count = 0): string {
    return preg_replace_callback(
        '~<div\b([^>]*)>~is',
        function ($m) use (&$count) {
            $attrs = $m[1];

            if (!preg_match_all('/\srole\s*=\s*("|\')(.*?)\1/is', $attrs, $roles, PREG_SET_ORDER)) {
                return '<div' . $attrs . '>';
            }

            $values = [];
            foreach ($roles as $r) {
                $values[] = strtolower(trim((string)$r[2]));
            }

            $unique = array_values(array_unique($values));

            if (count($unique) === 1 && $unique[0] === 'region') {
                return '<div' . $attrs . '>';
            }

            if (in_array('region', $unique, true) && count($unique) > 1) {
                $chosen = null;
                foreach ($unique as $u) {
                    if ($u !== 'region') {
                        $chosen = $u;
                        break;
                    }
                }
                if ($chosen === null) $chosen = 'region';

                $attrs = preg_replace('/\srole\s*=\s*("|\')(.*?)\1/is', '', $attrs);
                $attrs = preg_replace('/\s+/', ' ', $attrs);
                $attrs = rtrim($attrs);

                $count++;
                return '<div' . $attrs . ' role="' . htmlspecialchars($chosen, ENT_QUOTES, 'UTF-8') . '">';
            }

            return '<div' . $attrs . '>';
        },
        $html
    );
}

/**
 * يمنع إضافة region لعناصر لها roles دلالية أصلًا
 */
function patch_html_auto_fixer_source(string $file): bool {
    if (!is_file($file)) return false;
    $src = safe_get($file);
    if ($src === '') return false;

    $old = $src;

    $src = preg_replace(
        '~function fix_div_aria_without_role\(string \$html\): array \{.*?\n\}~is',
        <<<'PHP'
function fix_div_aria_without_role(string $html): array {
    $count = 0;

    $html = preg_replace_callback(
        '~<div\b([^>]*?)\saria-label\s*=\s*("|\')(.*?)\2([^>]*)>~is',
        function ($m) use (&$count) {
            $before = $m[1];
            $label  = $m[3];
            $after  = $m[4];
            $fullAttrs = $before . $after;

            if (preg_match('/\srole\s*=/i', $fullAttrs)) {
                return '<div' . $before . ' aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"' . $after . '>';
            }

            if (trim($label) === '') {
                $count++;
                return '<div' . $fullAttrs . '>';
            }

            $count++;
            return '<div' . $before . ' role="region" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"' . $after . '>';
        },
        $html
    );

    return [$html, $count];
}
PHP,
        $src,
        1
    );

    return $src !== $old ? safe_put($file, $src) : true;
}

$targetFixer = $ROOT . '/admin/system/html_auto_fixer.php';

?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>Fix HTML Auto Fixer Roles</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;background:#f8fafc;font-family:Tahoma,Arial,sans-serif">
<div style="max-width:1000px;margin:24px auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden">
<div style="padding:16px 20px;background:#0f172a;color:#fff;font-weight:700">إصلاح مشاكل role الناتجة عن HTML Auto Fixer</div>
<?php

if (!$RUN) {
    echo '<div style="padding:20px">';
    echo '<p>هذا السكربت سيقوم بتنظيف role المكررة من ملفات القالب، ثم يصحح كود الأداة نفسها.</p>';
    echo '<a href="?run=1" style="display:inline-block;background:#0f172a;color:#fff;padding:10px 16px;border-radius:10px;text-decoration:none">ابدأ الإصلاح</a>';
    echo '</div></div></body></html>';
    exit;
}

$files = find_files($ROOT);
$totalTouched = 0;
$totalRoleDedupe = 0;
$totalRegionCleanup = 0;

foreach ($files as $file) {
    $html = safe_get($file);
    if ($html === '') continue;

    $original = $html;
    $c1 = 0;
    $c2 = 0;

    $html = dedupe_role_attributes($html, $c1);
    $html = remove_redundant_region_role($html, $c2);

    if ($html !== $original) {
        backup_file($file, $ROOT);
        if (safe_put($file, $html)) {
            $totalTouched++;
            $totalRoleDedupe += $c1;
            $totalRegionCleanup += $c2;
            out('تم تنظيف: ' . rel_path($file, $ROOT), 'ok');
        } else {
            out('فشل التحديث: ' . rel_path($file, $ROOT), 'err');
        }
    }
}

if (is_file($targetFixer)) {
    backup_file($targetFixer, $ROOT);
    if (patch_html_auto_fixer_source($targetFixer)) {
        out('تم تصحيح كود html_auto_fixer.php نفسه لمنع تكرار role مستقبلاً.', 'ok');
    } else {
        out('تعذر تصحيح html_auto_fixer.php تلقائيًا.', 'warn');
    }
} else {
    out('ملف admin/system/html_auto_fixer.php غير موجود.', 'warn');
}

out('عدد الملفات المعدلة: ' . $totalTouched, 'info');
out('عدد عمليات إزالة role المكررة: ' . $totalRoleDedupe, 'info');
out('عدد عمليات تنظيف region الزائدة: ' . $totalRegionCleanup, 'info');
out('الخطوة التالية: أعد فحص الصفحة عبر validator.', 'ok');

echo '</div></div></body></html>';