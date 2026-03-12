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

$pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;
if (!($pdo instanceof PDO)) {
    die('Database not available');
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM news_revisions WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) die('Revision not found');

$pageTitle = 'عرض مراجعة';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<div class="admin-content">
  <div class="container-fluid" style="max-width:1000px;margin:24px auto;">
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden">
      <div style="padding:16px 20px;background:#0f172a;color:#fff;font-weight:700">عرض مراجعة</div>
      <div style="padding:16px 20px">
        <h2><?= h((string)$row['title']) ?></h2>
        <div style="color:#64748b;margin-bottom:12px"><?= h((string)$row['created_at']) ?></div>
        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:16px;line-height:1.9">
          <?= (string)$row['content'] ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>