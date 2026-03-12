<?php
declare(strict_types=1);

require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';

use Godyar\Auth;

if (!Auth::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if (!function_exists('h')) {
    function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$currentPage = 'system_health';
$pageTitle = 'المراقبة الحية';

$pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;

$stats = [
    'php_version' => PHP_VERSION,
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'server_time' => date('Y-m-d H:i:s'),
    'db_ok' => false,
    'cache_ok' => false,
    'news_count' => 0,
    'comments_count' => 0,
    'users_count' => 0,
];

if ($pdo instanceof PDO) {
    try {
        $pdo->query('SELECT 1');
        $stats['db_ok'] = true;
    } catch (Throwable $e) {}

    try { $stats['news_count'] = (int)($pdo->query("SELECT COUNT(*) FROM news")->fetchColumn() ?? 0); } catch (Throwable $e) {}
    try {
        $st = $pdo->query("SHOW TABLES LIKE 'news_comments'");
        if ($st && $st->fetchColumn()) {
            $stats['comments_count'] = (int)($pdo->query("SELECT COUNT(*) FROM news_comments")->fetchColumn() ?? 0);
        } else {
            $stats['comments_count'] = (int)($pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn() ?? 0);
        }
    } catch (Throwable $e) {}
    try { $stats['users_count'] = (int)($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?? 0); } catch (Throwable $e) {}
}

if (class_exists('Cache')) {
    try {
        Cache::put('_monitor_ping', 'ok', 60);
        $stats['cache_ok'] = Cache::get('_monitor_ping') === 'ok';
        Cache::forget('_monitor_ping');
    } catch (Throwable $e) {}
}

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<div class="admin-content">
  <div class="container-fluid" style="max-width:1200px;margin:24px auto;">
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden">
      <div style="padding:16px 20px;background:#0f172a;color:#fff;font-weight:700">المراقبة الحية</div>
      <div style="padding:18px 20px">
        <div class="row g-3 mb-3">
          <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">الأخبار</div><div style="font-size:1.6rem;font-weight:800"><?= (int)$stats['news_count'] ?></div></div></div></div>
          <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">التعليقات</div><div style="font-size:1.6rem;font-weight:800"><?= (int)$stats['comments_count'] ?></div></div></div></div>
          <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">المستخدمون</div><div style="font-size:1.6rem;font-weight:800"><?= (int)$stats['users_count'] ?></div></div></div></div>
          <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">وقت الخادم</div><div style="font-size:1rem;font-weight:800"><?= h($stats['server_time']) ?></div></div></div></div>
        </div>

        <table class="table table-bordered align-middle">
          <tr><th style="width:260px">PHP</th><td><?= h($stats['php_version']) ?></td></tr>
          <tr><th>memory_limit</th><td><?= h($stats['memory_limit']) ?></td></tr>
          <tr><th>upload_max_filesize</th><td><?= h($stats['upload_max_filesize']) ?></td></tr>
          <tr><th>post_max_size</th><td><?= h($stats['post_max_size']) ?></td></tr>
          <tr><th>قاعدة البيانات</th><td><?= $stats['db_ok'] ? '<span class="badge bg-success">متصلة</span>' : '<span class="badge bg-danger">فشل</span>' ?></td></tr>
          <tr><th>الكاش</th><td><?= $stats['cache_ok'] ? '<span class="badge bg-success">يعمل</span>' : '<span class="badge bg-warning text-dark">غير مستقر</span>' ?></td></tr>
        </table>

        <div class="d-flex gap-2 flex-wrap mt-3">
          <a href="/admin/comments/index.php" class="btn btn-primary btn-sm">إدارة التعليقات</a>
          <a href="/admin/system/health.php" class="btn btn-secondary btn-sm">صحة النظام</a>
          <a href="/admin/news/index.php" class="btn btn-dark btn-sm">إدارة الأخبار</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>