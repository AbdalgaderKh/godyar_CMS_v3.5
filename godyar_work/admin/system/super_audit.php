<?php
declare(strict_types=1);

set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', '1');

$CURRENT_DIR = realpath(__DIR__) ?: __DIR__;
$ROOT_CANDIDATES = [
    realpath($CURRENT_DIR . '/../../'),
    realpath($CURRENT_DIR . '/../'),
    realpath($_SERVER['DOCUMENT_ROOT'] ?? ''),
];

$ROOT = '';
foreach ($ROOT_CANDIDATES as $candidate) {
    if (is_string($candidate) && $candidate !== '' && is_dir($candidate . '/admin') && is_dir($candidate . '/includes')) {
        $ROOT = $candidate;
        break;
    }
}
if ($ROOT === '') {
    $ROOT = '/home/geqzylcq/public_html';
}

$NOW = date('Y-m-d H:i:s');
$IS_CLI = (PHP_SAPI === 'cli');

const GDY_SUPER_AUDIT_VERSION = '2.1.0';

function gdy_e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function gdy_out(string $msg): void {
    global $IS_CLI;
    if ($IS_CLI) echo trim(strip_tags($msg)) . PHP_EOL;
    else { echo $msg . "\n"; @ob_flush(); @flush(); }
}
function gdy_norm_path(string $path): string { return str_replace('\\', '/', $path); }
function gdy_rel_path(string $path, string $root): string {
    $path = gdy_norm_path($path);
    $root = rtrim(gdy_norm_path($root), '/');
    return ltrim(str_replace($root, '', $path), '/');
}
function gdy_safe_get(string $file): string {
    $c = @file_get_contents($file);
    return is_string($c) ? $c : '';
}
function gdy_human_bytes(int $bytes): string {
    $units = ['B','KB','MB','GB','TB'];
    $i = 0; $n = $bytes;
    while ($n >= 1024 && $i < count($units) - 1) { $n /= 1024; $i++; }
    return round($n, 2) . ' ' . $units[$i];
}
function gdy_issue(array &$issues, string $severity, string $category, string $title, string $details, array $meta = []): void {
    $issues[] = compact('severity','category','title','details','meta');
}
function gdy_penalty(string $severity): int {
    return match($severity) {
        'critical' => 25,
        'high' => 15,
        'medium' => 8,
        'low' => 3,
        default => 1,
    };
}
function gdy_status_from_score(int $score): array {
    if ($score >= 90) return ['ممتاز','success'];
    if ($score >= 75) return ['جيد جدًا','primary'];
    if ($score >= 60) return ['جيد','info'];
    if ($score >= 40) return ['متوسط','warning'];
    return ['بحاجة إلى تدخل','danger'];
}
function gdy_php_bin(): string {
    foreach ([PHP_BINARY, '/usr/bin/php', '/usr/local/bin/php', 'php'] as $bin) {
        if (is_string($bin) && trim($bin) !== '') return $bin;
    }
    return 'php';
}
function gdy_run_php_lint(string $file): array {
    $php = gdy_php_bin();
    $cmd = escapeshellcmd($php) . ' -d display_errors=1 -l ' . escapeshellarg($file) . ' 2>&1';
    $output = function_exists('shell_exec') ? @shell_exec($cmd) : null;

    if (!is_string($output) || trim($output) === '') {
        return ['ok' => true, 'message' => 'lint skipped'];
    }

    $clean = trim($output);

    if (
        stripos($clean, 'libxml') !== false &&
        stripos($clean, 'Parse error') === false &&
        stripos($clean, 'Fatal error') === false &&
        stripos($clean, 'Errors parsing') === false
    ) {
        return ['ok' => true, 'message' => 'environment warning ignored'];
    }

    if (stripos($clean, 'No syntax errors detected') !== false) {
        return ['ok' => true, 'message' => 'OK'];
    }

    if (
        stripos($clean, 'Parse error') !== false ||
        stripos($clean, 'Fatal error') !== false ||
        stripos($clean, 'Errors parsing') !== false
    ) {
        return ['ok' => false, 'message' => $clean];
    }

    return ['ok' => true, 'message' => 'OK'];
}

function gdy_is_excluded(string $rel): bool {
    return (bool)preg_match(
        '~(^|/)(vendor|node_modules|\.git|storage/audit_reports|_phase\d+_backups|backups?|1)(/|$)|(^|/)(diag\.php|frontend-diagnostic\.php|system_audit\.php|super_audit\.php|update_super_audit_v2\.php)$~i',
        $rel
    );
}

function gdy_find_files(string $root, array $extensions = ['php','js','css','html']): array {
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    $files = [];
    foreach ($rii as $file) {
        /** @var SplFileInfo $file */
        if (!$file->isFile()) continue;
        $path = $file->getPathname();
        $rel  = gdy_rel_path($path, $root);
        if (gdy_is_excluded($rel)) continue;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, $extensions, true)) $files[] = $path;
    }
    sort($files);
    return $files;
}

function gdy_bootstrap_project(string $root): array {
    $issues = [];
    $pdo = null;
    $bootstrap = $root . '/includes/bootstrap.php';
    if (!is_file($bootstrap)) {
        gdy_issue($issues, 'critical', 'bootstrap', 'ملف bootstrap غير موجود', 'تعذر العثور على includes/bootstrap.php');
        return ['pdo' => null, 'issues' => $issues];
    }
    try {
        require_once $bootstrap;
        if (function_exists('gdy_pdo_safe')) $pdo = gdy_pdo_safe();
    } catch (Throwable $e) {
        gdy_issue($issues, 'critical', 'bootstrap', 'فشل تحميل bootstrap', $e->getMessage());
    }
    return ['pdo' => ($pdo instanceof PDO ? $pdo : null), 'issues' => $issues];
}

function gdy_architecture_audit(string $root): array {
    $issues = [];
    foreach (['admin','frontend','includes','assets'] as $dir) {
        if (!is_dir($root . '/' . $dir)) {
            gdy_issue($issues, 'high', 'architecture', 'مجلد أساسي غير موجود', "المجلد {$dir} غير موجود.");
        }
    }
    return $issues;
}

function gdy_filesystem_audit(string $root): array {
    $issues = [];
    $data = ['dirs' => [], 'files' => []];
    $dirs = [
        'cache' => $root . '/cache',
        'uploads' => $root . '/uploads',
        'logs' => $root . '/logs',
        'assets' => $root . '/assets',
        'admin' => $root . '/admin',
        'frontend' => $root . '/frontend',
        'includes' => $root . '/includes',
        'storage' => $root . '/storage',
    ];

    foreach ($dirs as $name => $path) {
        $exists = is_dir($path);
        $writable = $exists ? is_writable($path) : false;
        $data['dirs'][] = [
            'name' => $name,
            'path' => $path,
            'exists' => $exists,
            'writable' => $writable,
            'perms' => $exists ? substr(sprintf('%o', (int)@fileperms($path)), -4) : null,
        ];

        if (!$exists) {
            gdy_issue($issues, 'medium', 'filesystem', 'مجلد مهم غير موجود', "{$name}: {$path}");
        } elseif (in_array($name, ['cache','uploads','logs','storage'], true) && !$writable) {
            gdy_issue($issues, 'high', 'filesystem', 'مجلد غير قابل للكتابة', "{$name}: {$path}");
        }
    }

    foreach (['robots.txt','sitemap.php','index.php'] as $f) {
        $data['files'][] = ['name' => $f, 'exists' => is_file($root . '/' . $f)];
    }

    return ['data' => $data, 'issues' => $issues];
}

function gdy_database_audit(?PDO $pdo): array {
    $issues = [];
    $data = [
        'connected' => false,
        'driver' => null,
        'server_version' => null,
        'database_name' => null,
        'tables' => [],
    ];

    if (!($pdo instanceof PDO)) {
        gdy_issue($issues, 'critical', 'database', 'قاعدة البيانات غير متصلة', 'تعذر الحصول على PDO.');
        return ['data' => $data, 'issues' => $issues];
    }

    try {
        $pdo->query('SELECT 1');
        $data['connected'] = true;
        $data['driver'] = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $data['server_version'] = (string)$pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        try { $data['database_name'] = (string)$pdo->query('SELECT DATABASE()')->fetchColumn(); } catch (Throwable $e) {}
    } catch (Throwable $e) {
        gdy_issue($issues, 'critical', 'database', 'فشل اختبار قاعدة البيانات', $e->getMessage());
        return ['data' => $data, 'issues' => $issues];
    }

    try {
        $rows = $pdo->query('SHOW TABLE STATUS')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $r) {
            $data['tables'][] = [
                'name' => (string)($r['Name'] ?? ''),
                'engine' => (string)($r['Engine'] ?? ''),
                'rows' => (int)($r['Rows'] ?? 0),
                'collation' => (string)($r['Collation'] ?? ''),
            ];
        }
    } catch (Throwable $e) {
        gdy_issue($issues, 'medium', 'database', 'تعذر قراءة حالة الجداول', $e->getMessage());
    }

    $tableNames = array_map(fn($t) => strtolower((string)$t['name']), $data['tables']);
    foreach (['news','categories','users'] as $tbl) {
        if (!in_array($tbl, $tableNames, true)) {
            gdy_issue($issues, 'high', 'database', 'جدول أساسي غير موجود', "الجدول {$tbl} غير موجود.");
        }
    }
    if (!in_array('comments', $tableNames, true) && !in_array('news_comments', $tableNames, true)) {
        gdy_issue($issues, 'high', 'database', 'جداول التعليقات غير موجودة', 'لم يتم العثور على comments أو news_comments.');
    }

    return ['data' => $data, 'issues' => $issues];
}

function gdy_security_audit_file(string $file, string $root): array {
    $issues = [];
    $code = gdy_safe_get($file);
    $rel = gdy_rel_path($file, $root);
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($code === '' || $ext !== 'php') return $issues;

    if (preg_match('/\beval\s*\(/i', $code)) {
        gdy_issue($issues, 'critical', 'security', 'استخدام eval', "تم العثور على eval() داخل {$rel}.");
    }

    if (preg_match('/\b(shell_exec|exec|system|passthru|proc_open|popen)\s*\(/i', $code)) {
        gdy_issue($issues, 'medium', 'security', 'أوامر نظام مباشرة', "يوجد استدعاء أوامر نظام داخل {$rel}.", [
            'suggestion' => 'تحقق يدويًا أن الاستخدام مقصود وآمن.'
        ]);
    }

    if (
        preg_match('/\$_(GET|POST|REQUEST|COOKIE)/i', $code) &&
        preg_match('/\b(SELECT|INSERT|UPDATE|DELETE)\b/i', $code) &&
        !preg_match('/->prepare\s*\(/i', $code)
    ) {
        gdy_issue($issues, 'high', 'security', 'احتمال SQL Injection', "يوجد استعلام SQL مع مدخلات مستخدم بدون prepare في {$rel}.", [
            'suggestion' => 'استخدم prepared statements.',
            'code' => "\$stmt = \$pdo->prepare('SELECT * FROM news WHERE id = ?');\n\$stmt->execute([\$id]);"
        ]);
    }

    if (
        preg_match('/echo\s+\$_(GET|POST|REQUEST|COOKIE)/i', $code) ||
        preg_match('/<\?=\s*\$_(GET|POST|REQUEST|COOKIE)/i', $code)
    ) {
        gdy_issue($issues, 'high', 'security', 'احتمال XSS', "يوجد إخراج مباشر لمدخلات المستخدم داخل {$rel}.", [
            'code' => "echo htmlspecialchars((string)\$_GET['q'], ENT_QUOTES, 'UTF-8');"
        ]);
    }

    if (preg_match('/<form\b/i', $code) && !preg_match('/csrf_field|csrf_token|verify_csrf/i', $code)) {
        gdy_issue($issues, 'medium', 'security', 'نموذج بدون إشارة CSRF واضحة', "الملف {$rel} يحتوي form بدون حماية CSRF واضحة.");
    }

    if (preg_match('/move_uploaded_file\s*\(/i', $code) && !preg_match('/finfo|mime|SafeUploader|allowed_ext|allowed_mime/i', $code)) {
        gdy_issue($issues, 'high', 'security', 'رفع ملفات محتمل غير آمن', "يوجد move_uploaded_file بدون فحص واضح للنوع في {$rel}.");
    }

    return $issues;
}

function gdy_quality_audit_file(string $file, string $root): array {
    $issues = [];
    $stats = [
        'lines' => 0,
        'long_lines' => 0,
        'todo' => 0,
        'fixme' => 0,
        'var_dump' => 0,
        'print_r' => 0,
        'die' => 0,
        'exit' => 0,
        'inline_css_blocks' => 0,
        'inline_js_blocks' => 0,
    ];

    $code = gdy_safe_get($file);
    $rel = gdy_rel_path($file, $root);
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($code === '') return ['issues' => $issues, 'stats' => $stats];

    $lines = preg_split("/\R/u", $code) ?: [];
    $stats['lines'] = count($lines);

    foreach ($lines as $line) {
        if (mb_strlen($line) > 180) $stats['long_lines']++;
    }

    $stats['todo'] = preg_match_all('/\bTODO\b/i', $code);
    $stats['fixme'] = preg_match_all('/\bFIXME\b/i', $code);
    $stats['var_dump'] = preg_match_all('/\bvar_dump\s*\(/i', $code);
    $stats['print_r'] = preg_match_all('/\bprint_r\s*\(/i', $code);
    $stats['die'] = preg_match_all('/\bdie\s*\(/i', $code);
    $stats['exit'] = preg_match_all('/\bexit\s*\(?/i', $code);
    $stats['inline_css_blocks'] = preg_match_all('/<style\b/i', $code);
    $stats['inline_js_blocks'] = preg_match_all('/<script\b/i', $code);

    if ($stats['long_lines'] > 20) {
        gdy_issue($issues, 'low', 'quality', 'أسطر طويلة بكثرة', "{$rel} يحتوي {$stats['long_lines']} سطرًا طويلًا.");
    }
    if ($stats['todo'] > 0 || $stats['fixme'] > 0) {
        gdy_issue($issues, 'low', 'quality', 'ملاحظات TODO/FIXME', "{$rel} يحتوي TODO أو FIXME.");
    }
    if ($stats['var_dump'] > 0 || $stats['print_r'] > 0) {
        gdy_issue($issues, 'low', 'quality', 'أدوات Debug موجودة', "يوجد var_dump/print_r في {$rel}.");
    }
    if ($ext === 'php' && ($stats['die'] > 0 || $stats['exit'] > 5)) {
        gdy_issue($issues, 'low', 'quality', 'إنهاء مباشر للتنفيذ', "يوجد die()/exit() في {$rel}.");
    }
    if ($stats['inline_css_blocks'] > 0 && str_contains($rel, 'admin/')) {
        gdy_issue($issues, 'low', 'quality', 'CSS مضمن في صفحة إدارة', "يوجد style blocks داخل {$rel}.", [
            'suggestion' => 'انقل CSS إلى ملف خارجي.'
        ]);
    }
    if ($stats['inline_js_blocks'] > 2 && str_contains($rel, 'admin/')) {
        gdy_issue($issues, 'low', 'quality', 'JavaScript مضمن بكثرة', "يوجد script blocks كثيرة داخل {$rel}.", [
            'suggestion' => 'انقل JS إلى ملف خارجي.'
        ]);
    }

    return ['issues' => $issues, 'stats' => $stats];
}

function gdy_seo_audit(string $root): array {
    $issues = [];
    $data = [
        'robots_txt' => is_file($root . '/robots.txt'),
        'sitemap_php' => is_file($root . '/sitemap.php'),
        'sitemap_xml' => is_file($root . '/sitemap.xml'),
        'frontend_meta_description_hits' => 0,
    ];

    if (!$data['robots_txt']) {
        gdy_issue($issues, 'medium', 'seo', 'robots.txt غير موجود', 'يفضل وجود robots.txt في الجذر.');
    }
    if (!$data['sitemap_php'] && !$data['sitemap_xml']) {
        gdy_issue($issues, 'high', 'seo', 'sitemap غير موجود', 'لا يوجد sitemap.php أو sitemap.xml.');
    }

    $frontend = $root . '/frontend';
    if (is_dir($frontend)) {
        foreach (gdy_find_files($frontend, ['php','html']) as $f) {
            $code = gdy_safe_get($f);
            if (preg_match('/meta\s+name=["\']description["\']/i', $code)) {
                $data['frontend_meta_description_hits']++;
            }
        }
    }

    return ['data' => $data, 'issues' => $issues];
}

function gdy_performance_audit(string $root): array {
    $issues = [];
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($rii as $file) {
        /** @var SplFileInfo $file */
        if (!$file->isFile()) continue;
        $path = $file->getPathname();
        $rel = gdy_rel_path($path, $root);
        if (gdy_is_excluded($rel)) continue;

        $size = (int)$file->getSize();
        if ($size > 5 * 1024 * 1024) {
            gdy_issue($issues, 'low', 'performance', 'ملف كبير', "{$rel} حجمه " . gdy_human_bytes($size));
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'php') {
            $content = gdy_safe_get($path);
            $lineCount = substr_count($content, "\n") + 1;
            if ($lineCount > 1200) {
                gdy_issue($issues, 'low', 'performance', 'ملف PHP ضخم', "{$rel} يحتوي تقريبًا {$lineCount} سطر.", [
                    'suggestion' => 'فكك الملف إلى partials أو services أو assets.'
                ]);
            }
        }
    }

    return $issues;
}

function gdy_build_recommendations(array $issues): array {
    $recs = [];

    $has = function(string $needle) use ($issues): bool {
        foreach ($issues as $i) {
            $hay = mb_strtolower(($i['title'] ?? '') . ' ' . ($i['details'] ?? ''));
            if (str_contains($hay, mb_strtolower($needle))) return true;
        }
        return false;
    };

    if ($has('sql injection')) {
        $recs[] = [
            'title' => 'تأمين الاستعلامات',
            'details' => 'استخدم prepared statements في كل الاستعلامات المعتمدة على مدخلات المستخدم.',
            'code' => "\$stmt = \$pdo->prepare('SELECT * FROM news WHERE id = ?');\n\$stmt->execute([\$id]);"
        ];
    }
    if ($has('css مضمن') || $has('javascript مضمن')) {
        $recs[] = [
            'title' => 'فصل CSS وJS',
            'details' => 'انقل الأكواد المضمنة إلى ملفات خارجية.',
            'code' => "<link rel=\"stylesheet\" href=\"/admin/assets/css/page.css\">\n<script src=\"/admin/assets/js/page.js\"></script>"
        ];
    }
    if ($has('csrf')) {
        $recs[] = [
            'title' => 'تعزيز حماية CSRF',
            'details' => 'أضف csrf_field() و verify_csrf() حيث يلزم.',
            'code' => "<?php echo csrf_field(); ?>\n<?php verify_csrf(); ?>"
        ];
    }
    if ($has('رفع ملفات')) {
        $recs[] = [
            'title' => 'تأمين رفع الملفات',
            'details' => 'تحقق من الامتداد وMIME واستخدم SafeUploader أو finfo.',
            'code' => "\$finfo = finfo_open(FILEINFO_MIME_TYPE);\n\$mime = finfo_file(\$finfo, \$_FILES['file']['tmp_name']);"
        ];
    }
    if (!$recs) {
        $recs[] = [
            'title' => 'الوضع العام جيد',
            'details' => 'لا توجد مشاكل حرجة واضحة بعد الفلترة.',
            'code' => ''
        ];
    }

    return $recs;
}

function gdy_top_issues(array $issues, int $limit = 25): array {
    usort($issues, function($a, $b) {
        $pa = gdy_penalty($a['severity'] ?? 'info');
        $pb = gdy_penalty($b['severity'] ?? 'info');
        return $pb <=> $pa;
    });
    return array_slice($issues, 0, $limit);
}

function gdy_render_html(array $report): string {
    [$label, $badge] = gdy_status_from_score((int)$report['scores']['overall']);
    ob_start();
    ?>
<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8">
<title>Godyar Super Audit V2</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{margin:0;background:#f8fafc;color:#0f172a;font-family:Tahoma,Arial,sans-serif}
.wrap{max-width:1280px;margin:24px auto;padding:0 16px}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,.06);margin-bottom:16px}
.head{padding:16px 20px;background:#0f172a;color:#fff;font-weight:800}
.body{padding:18px 20px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
.kpi{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:14px}
.kpi .t{color:#64748b;font-size:.9rem}
.kpi .v{font-size:1.4rem;font-weight:800;margin-top:6px}
.badge{display:inline-block;padding:.28rem .6rem;border-radius:999px;font-size:.8rem;font-weight:700}
.success{background:#dcfce7;color:#166534}
.primary{background:#dbeafe;color:#1d4ed8}
.info{background:#cffafe;color:#155e75}
.warning{background:#fef3c7;color:#92400e}
.danger{background:#fee2e2;color:#991b1b}
table{width:100%;border-collapse:collapse}
th,td{padding:10px 8px;border-bottom:1px solid #eef2f7;text-align:right;vertical-align:top}
th{background:#f8fafc}
.issue{border:1px solid #e5e7eb;border-radius:14px;padding:12px;margin-bottom:10px}
.issue h4{margin:0 0 6px;font-size:1rem}
pre{white-space:pre-wrap;background:#0f172a;color:#e5e7eb;padding:12px;border-radius:12px;overflow:auto}
</style>
</head>
<body>
<div class="wrap">

<div class="card">
<div class="head">Godyar Super Audit V2</div>
<div class="body">
<div class="grid">
<div class="kpi"><div class="t">وقت الفحص</div><div class="v"><?= gdy_e($report['meta']['scanned_at']) ?></div></div>
<div class="kpi"><div class="t">الملفات المفحوصة</div><div class="v"><?= (int)$report['meta']['files_scanned'] ?></div></div>
<div class="kpi"><div class="t">الحالة العامة</div><div class="v"><span class="badge <?= gdy_e($badge) ?>"><?= gdy_e($label) ?></span></div></div>
<div class="kpi"><div class="t">Overall</div><div class="v"><?= (int)$report['scores']['overall'] ?>/100</div></div>
<div class="kpi"><div class="t">Security</div><div class="v"><?= (int)$report['scores']['security'] ?>/100</div></div>
<div class="kpi"><div class="t">Performance</div><div class="v"><?= (int)$report['scores']['performance'] ?>/100</div></div>
<div class="kpi"><div class="t">Architecture</div><div class="v"><?= (int)$report['scores']['architecture'] ?>/100</div></div>
<div class="kpi"><div class="t">Critical</div><div class="v"><?= (int)$report['counts']['critical'] ?></div></div>
</div>
</div>
</div>

<div class="card">
<div class="head">ملخص البيئة</div>
<div class="body">
<table>
<tr><th>جذر المشروع</th><td><?= gdy_e($report['meta']['root']) ?></td></tr>
<tr><th>PHP</th><td><?= gdy_e($report['environment']['php_version']) ?> / <?= gdy_e($report['environment']['php_sapi']) ?></td></tr>
<tr><th>النظام</th><td><?= gdy_e($report['environment']['os']) ?></td></tr>
<tr><th>قاعدة البيانات</th><td><?= !empty($report['database']['connected']) ? 'متصلة' : 'غير متصلة' ?></td></tr>
<tr><th>Driver</th><td><?= gdy_e((string)($report['database']['driver'] ?? '-')) ?></td></tr>
<tr><th>DB Name</th><td><?= gdy_e((string)($report['database']['database_name'] ?? '-')) ?></td></tr>
</table>
</div>
</div>

<div class="card">
<div class="head">Top Issues</div>
<div class="body">
<?php if (empty($report['top_issues'])): ?>
<span class="badge success">لا توجد مشاكل بارزة بعد الفلترة</span>
<?php else: ?>
<?php foreach ($report['top_issues'] as $issue): ?>
<div class="issue">
<h4>
<span class="badge <?= gdy_e(match($issue['severity']) {
'critical' => 'danger',
'high' => 'warning',
'medium' => 'info',
'low' => 'primary',
default => 'success'
}) ?>"><?= gdy_e($issue['severity']) ?></span>
<?= gdy_e($issue['category']) ?> — <?= gdy_e($issue['title']) ?>
</h4>
<div><?= gdy_e($issue['details']) ?></div>
<?php if (!empty($issue['meta']['suggestion'])): ?>
<div style="margin-top:6px;color:#64748b"><?= gdy_e($issue['meta']['suggestion']) ?></div>
<?php endif; ?>
<?php if (!empty($issue['meta']['code'])): ?>
<pre><?= gdy_e($issue['meta']['code']) ?></pre>
<?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>

<div class="card">
<div class="head">التوصيات النهائية</div>
<div class="body">
<?php foreach ($report['recommendations'] as $rec): ?>
<div class="issue">
<h4><?= gdy_e($rec['title']) ?></h4>
<div><?= gdy_e($rec['details']) ?></div>
<?php if (!empty($rec['code'])): ?>
<pre><?= gdy_e($rec['code']) ?></pre>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</div>

</div>
</body>
</html>
    <?php
    return (string)ob_get_clean();
}

function gdy_render_markdown(array $report): string {
    $md = "# Godyar Super Audit V2\n\n";
    $md .= "- وقت الفحص: {$report['meta']['scanned_at']}\n";
    $md .= "- جذر المشروع: {$report['meta']['root']}\n";
    $md .= "- الملفات المفحوصة: {$report['meta']['files_scanned']}\n";
    $md .= "- Overall: {$report['scores']['overall']}/100\n";
    $md .= "- Security: {$report['scores']['security']}/100\n";
    $md .= "- Performance: {$report['scores']['performance']}/100\n";
    $md .= "- Architecture: {$report['scores']['architecture']}/100\n\n";

    $md .= "## Top Issues\n\n";
    foreach ($report['top_issues'] as $i) {
        $md .= "- **[{$i['severity']}] {$i['category']} / {$i['title']}** — {$i['details']}\n";
        if (!empty($i['meta']['suggestion'])) {
            $md .= "  - التوصية: {$i['meta']['suggestion']}\n";
        }
        if (!empty($i['meta']['code'])) {
            $md .= "```php\n{$i['meta']['code']}\n```\n";
        }
    }

    $md .= "\n## Recommendations\n\n";
    foreach ($report['recommendations'] as $r) {
        $md .= "### {$r['title']}\n\n{$r['details']}\n\n";
        if (!empty($r['code'])) {
            $md .= "```php\n{$r['code']}\n```\n\n";
        }
    }

    return $md;
}

/* RUN */
if (!$IS_CLI) {
    echo '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>Godyar Super Audit V2</title></head><body style="font-family:Tahoma,Arial,sans-serif;background:#f8fafc;padding:20px">';
    echo '<div style="max-width:980px;margin:auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden">';
    echo '<div style="padding:16px 20px;background:#0f172a;color:#fff;font-weight:700">بدء Godyar Super Audit V2...</div>';
    echo '<div style="padding:14px">';
}

gdy_out('<div>المجلد الحالي: ' . gdy_e($CURRENT_DIR) . '</div>');
gdy_out('<div>جذر المشروع المكتشف: ' . gdy_e($ROOT) . '</div>');
gdy_out('<div>DOCUMENT_ROOT: ' . gdy_e((string)($_SERVER['DOCUMENT_ROOT'] ?? '')) . '</div>');
gdy_out('<div>تحميل bootstrap...</div>');

$bootstrap = gdy_bootstrap_project($ROOT);
$pdo = $bootstrap['pdo'];
$issues = $bootstrap['issues'];

gdy_out('<div>فحص البنية...</div>');
$issues = array_merge($issues, gdy_architecture_audit($ROOT));

gdy_out('<div>فحص الملفات...</div>');
$projectFiles = gdy_find_files($ROOT, ['php','js','css','html']);

$syntaxErrors = [];
$codeStats = [
    'lines' => 0, 'long_lines' => 0, 'todo' => 0, 'fixme' => 0,
    'var_dump' => 0, 'print_r' => 0, 'die' => 0, 'exit' => 0,
    'inline_css_blocks' => 0, 'inline_js_blocks' => 0,
];

foreach ($projectFiles as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($ext === 'php') {
        $lint = gdy_run_php_lint($file);
        if (!$lint['ok']) {
            $syntaxErrors[] = ['file' => gdy_rel_path($file, $ROOT), 'message' => $lint['message']];
            gdy_issue($issues, 'high', 'syntax', 'Syntax error', 'يوجد خطأ Syntax في ' . gdy_rel_path($file, $ROOT));
        }
    }

    $qa = gdy_quality_audit_file($file, $ROOT);
    foreach ($qa['issues'] as $i) $issues[] = $i;
    foreach ($codeStats as $k => $v) {
        if (isset($qa['stats'][$k])) $codeStats[$k] += (int)$qa['stats'][$k];
    }

    foreach (gdy_security_audit_file($file, $ROOT) as $i) $issues[] = $i;
}

gdy_out('<div>فحص الملفات والمجلدات...</div>');
$fs = gdy_filesystem_audit($ROOT);
$issues = array_merge($issues, $fs['issues']);

gdy_out('<div>فحص قاعدة البيانات...</div>');
$db = gdy_database_audit($pdo);
$issues = array_merge($issues, $db['issues']);

gdy_out('<div>فحص SEO...</div>');
$seo = gdy_seo_audit($ROOT);
$issues = array_merge($issues, $seo['issues']);

gdy_out('<div>فحص الأداء...</div>');
$issues = array_merge($issues, gdy_performance_audit($ROOT));

$counts = ['critical'=>0,'high'=>0,'medium'=>0,'low'=>0,'info'=>0];
$totalPenalty = 0; $securityPenalty = 0; $performancePenalty = 0; $architecturePenalty = 0;

foreach ($issues as $i) {
    $sev = $i['severity'] ?? 'info';
    if (isset($counts[$sev])) $counts[$sev]++;
    $pen = gdy_penalty($sev);
    $totalPenalty += $pen;
    $cat = $i['category'] ?? '';
    if ($cat === 'security') $securityPenalty += $pen;
    if ($cat === 'performance') $performancePenalty += $pen;
    if (in_array($cat, ['architecture','bootstrap'], true)) $architecturePenalty += $pen;
}

$overallScore = max(0, min(100, 100 - $totalPenalty));
$securityScore = max(0, min(100, 100 - $securityPenalty));
$performanceScore = max(0, min(100, 100 - $performancePenalty));
$architectureScore = max(0, min(100, 100 - $architecturePenalty));

$report = [
    'meta' => [
        'version' => GDY_SUPER_AUDIT_VERSION,
        'scanned_at' => $NOW,
        'root' => $ROOT,
        'files_scanned' => count($projectFiles),
    ],
    'environment' => [
        'php_version' => PHP_VERSION,
        'php_sapi' => PHP_SAPI,
        'os' => php_uname('s') . ' ' . php_uname('r'),
    ],
    'database' => $db['data'],
    'filesystem' => $fs['data'],
    'seo' => $seo['data'],
    'syntax_errors' => $syntaxErrors,
    'code_stats' => $codeStats,
    'issues' => $issues,
    'top_issues' => gdy_top_issues($issues, 25),
    'counts' => $counts,
    'scores' => [
        'overall' => $overallScore,
        'security' => $securityScore,
        'performance' => $performanceScore,
        'architecture' => $architectureScore,
    ],
    'recommendations' => gdy_build_recommendations($issues),
];

$reportsDir = rtrim($ROOT, '/\\') . '/storage/audit_reports';
if (!is_dir($reportsDir)) {
    if (!@mkdir($reportsDir, 0755, true) && !is_dir($reportsDir)) {
        die('تعذر إنشاء مجلد التقارير: ' . $reportsDir);
    }
}
if (!is_writable($reportsDir)) {
    die('مجلد التقارير غير قابل للكتابة: ' . $reportsDir);
}

$stamp = date('Ymd_His');
$htmlPath = $reportsDir . "/super_audit_v2_report_{$stamp}.html";
$jsonPath = $reportsDir . "/super_audit_v2_report_{$stamp}.json";
$mdPath   = $reportsDir . "/super_audit_v2_report_{$stamp}.md";

file_put_contents($htmlPath, gdy_render_html($report));
file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
file_put_contents($mdPath, gdy_render_markdown($report));

gdy_out('<div>اكتمل الفحص.</div>');
gdy_out('<div>Overall Score: <strong>' . $overallScore . '/100</strong></div>');
gdy_out('<div>Security Score: <strong>' . $securityScore . '/100</strong></div>');
gdy_out('<div>Performance Score: <strong>' . $performanceScore . '/100</strong></div>');
gdy_out('<div>Architecture Score: <strong>' . $architectureScore . '/100</strong></div>');
gdy_out('<div>تم إنشاء التقارير في: ' . gdy_e($reportsDir) . '</div>');

if (!$IS_CLI) {
    $baseHrefHtml = '/storage/audit_reports/' . basename($htmlPath);
    $baseHrefJson = '/storage/audit_reports/' . basename($jsonPath);
    $baseHrefMd   = '/storage/audit_reports/' . basename($mdPath);

    echo '<div style="margin-top:14px;padding:14px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px">';
    echo '<div style="margin-bottom:8px;font-weight:700">الملفات الناتجة</div>';
    echo '<div><a href="' . gdy_e($baseHrefHtml) . '" target="_blank">فتح تقرير HTML</a></div>';
    echo '<div><a href="' . gdy_e($baseHrefJson) . '" target="_blank">فتح تقرير JSON</a></div>';
    echo '<div><a href="' . gdy_e($baseHrefMd) . '" target="_blank">فتح تقرير Markdown</a></div>';
    echo '</div>';
    echo '</div></div></body></html>';
}