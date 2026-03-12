<?php
declare(strict_types=1);

require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/news_revisions.php';

use Godyar\Auth;

if (!Auth::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if (!function_exists('h')) {
    function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;
if (!($pdo instanceof PDO)) {
    die('Database not available');
}

$newsId = (int)($_GET['news_id'] ?? 0);
if ($newsId <= 0) {
    die('Invalid news id');
}

$pageTitle = 'مراجعات الخبر';
$currentPage = 'posts';

$rows = [];
if (gdy_news_revisions_table_exists($pdo)) {
    $stmt = $pdo->prepare("SELECT * FROM news_revisions WHERE news_id = ? ORDER BY id DESC LIMIT 100");
    $stmt->execute([$newsId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<div class="admin-content">
  <div class="container-fluid" style="max-width:1100px;margin:24px auto;">
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden">
      <div style="padding:16px 20px;background:#0f172a;color:#fff;font-weight:700">مراجعات الخبر #<?= (int)$newsId ?></div>
      <div style="padding:16px 20px">
        <table class="table table-bordered align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>العنوان</th>
              <th>الحالة</th>
              <th>التاريخ</th>
              <th>إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= h((string)$row['title']) ?></td>
                <td><?= h((string)$row['status']) ?></td>
                <td><?= h((string)$row['created_at']) ?></td>
                <td>
                  <a class="btn btn-sm btn-outline-primary" href="view_revision.php?id=<?= (int)$row['id'] ?>">عرض</a>
                  <a class="btn btn-sm btn-outline-success" href="restore_revision.php?id=<?= (int)$row['id'] ?>" onclick="return confirm('استرجاع هذه المراجعة؟')">استرجاع</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="5">لا توجد مراجعات محفوظة.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>