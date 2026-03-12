<?php
require_once __DIR__ . '/../../_admin_guard.php';
require_once __DIR__ . '/../../../includes/bootstrap.php';

use Godyar\Auth;

$currentPage = 'system_health';
$pageTitle = __('t_63163058e0', 'صحة النظام');

if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$cspNonce = $cspNonce ?? '';

try {
    if (class_exists(Auth::class) && method_exists(Auth::class, 'isLoggedIn')) {
        if (!Auth::isLoggedIn()) {
            header('Location: ../../login.php');
            exit;
        }
    } else {
        if (empty($_SESSION['user']) || (((!empty($_SESSION['user']['role']) ? $_SESSION['user']['role'] : '')) === 'guest')) {
            header('Location: ../../login.php');
            exit;
        }
    }
} catch (\Throwable $e) {
    error_log('[Godyar Health] Auth error: ' . $e->getMessage());
    header('Location: ../../login.php');
    exit;
}

$pdo = gdy_pdo_safe();

$checks = [
    'php_version' => PHP_VERSION,
    'php_sapi' => PHP_SAPI,
    'os' => php_uname('s') . ' ' . php_uname('r'),
    'timezone' => date_default_timezone_get(),
    'now' => date('Y-m-d H:i:s'),
    'db_ok' => false,
    'db_driver' => null,
    'db_server' => null,
    'cache_ok' => false,
    'cache_driver' => 'none',
];

$dbError = null;

if ($pdo instanceof PDO) {
    try {
        $pdo->query("SELECT 1");
        $checks['db_ok'] = true;
        $checks['db_driver'] = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $checks['db_server'] = (string)$pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    } catch (\Throwable $e) {
        $dbError = $e->getMessage();
    }
} else {
    $dbError = __('t_d3618334c1', 'لم يتم تهيئة اتصال قاعدة البيانات.');
}

if (class_exists('Cache')) {
    $checks['cache_driver'] = 'file';
    try {
        Cache::put('_health_test', 'ok', 60);
        $val = Cache::get('_health_test');
        $checks['cache_ok'] = ($val === 'ok');
        Cache::forget('_health_test');
    } catch (\Throwable $e) {
        $checks['cache_ok'] = false;
    }
}

$phpIni = [
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'display_errors' => ini_get('display_errors'),
];

$appEnv = getenv('APP_ENV') ?: 'production';
$appDebug = (bool)getenv('APP_DEBUG');
$appUrl = getenv('APP_URL') ?: '';

$rootPath = realpath(__DIR__ . '/../../../') ?: dirname(__DIR__, 3);

$fsChecks = [];

$dirsToCheck = [
    'cache' => $rootPath . '/cache',
    'uploads' => $rootPath . '/uploads',
    'logs' => $rootPath . '/logs',
];

foreach ($dirsToCheck as $label => $path) {
    $fsChecks[] = [
        'label' => $label,
        'path' => $path,
        'exists' => is_dir($path),
        'writable' => is_writable($path),
    ];
}

$score = 0;

if (version_compare(PHP_VERSION, '8.0.0', '>=')) $score += 40;
if ($checks['db_ok']) $score += 40;
if ($checks['cache_ok']) $score += 20;

$overallLabel = 'متوسط';
$overallClass = 'bg-warning';

if ($score >= 90) {
    $overallLabel = 'ممتاز';
    $overallClass = 'bg-success';
} elseif ($score <= 40) {
    $overallLabel = 'بحاجة إلى تدخل';
    $overallClass = 'bg-danger';
}

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div class="admin-content container-fluid py-4">

<div class="container-fluid" style="max-width:1100px">

<div class="card shadow-sm border-0 mb-3">
<div class="card-body">

<h4 class="mb-3">صحة النظام</h4>

<div class="mb-3">
<span class="badge <?= h($overallClass) ?>">
التقييم العام: <?= h($overallLabel) ?> (<?= $score ?>%)
</span>
</div>

<table class="table table-sm">
<tr>
<td>PHP Version</td>
<td><?= h($checks['php_version']) ?></td>
</tr>

<tr>
<td>Database</td>
<td>
<?php if ($checks['db_ok']): ?>
<span class="badge bg-success">Connected</span>
<?php else: ?>
<span class="badge bg-danger">Failed</span>
<?php endif; ?>
</td>
</tr>

<tr>
<td>Cache</td>
<td>
<?php if ($checks['cache_ok']): ?>
<span class="badge bg-success">Working</span>
<?php else: ?>
<span class="badge bg-warning text-dark">Not Active</span>
<?php endif; ?>
</td>
</tr>

<tr>
<td>Timezone</td>
<td><?= h($checks['timezone']) ?></td>
</tr>

<tr>
<td>Server Time</td>
<td><?= h($checks['now']) ?></td>
</tr>

</table>

</div>
</div>

<div class="card shadow-sm border-0">
<div class="card-body">

<h5 class="mb-3">روابط إدارية سريعة</h5>

<a href="/admin/comments/index.php" class="btn btn-primary btn-sm">
إدارة التعليقات
</a>

<a href="/admin/news/index.php" class="btn btn-secondary btn-sm">
إدارة الأخبار
</a>

<a href="/admin/system/health.php" class="btn btn-dark btn-sm">
فحص النظام
</a>

</div>
</div>

</div>
</div>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>