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
    function h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;
if (!($pdo instanceof PDO)) {
    die('Database not available');
}

$pageTitle = 'إدارة التعليقات';
$currentPage = 'comments';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

$flashSuccess = $_SESSION['success_message'] ?? '';
$flashError   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$table = null;

try {
    $st = $pdo->query("SHOW TABLES LIKE 'news_comments'");
    if ($st->fetchColumn()) {
        $table = 'news_comments';
    }
} catch (Throwable $e) {
}

if ($table === null) {
    try {
        $st = $pdo->query("SHOW TABLES LIKE 'comments'");
        if ($st->fetchColumn()) {
            $table = 'comments';
        }
    } catch (Throwable $e) {
    }
}

if ($table === null) {
    die('Comments table not found');
}

$q = trim((string)($_GET['q'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));

$where = [];
$params = [];

if ($q !== '') {
    if ($table === 'news_comments') {
        $where[] = '(COALESCE(name,"") LIKE ? OR COALESCE(body,"") LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
    } else {
        $where[] = '(COALESCE(author_name,"") LIKE ? OR COALESCE(content,"") LIKE ? OR COALESCE(body,"") LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
}

if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}

$countSql = "SELECT COUNT(*) FROM {$table}";
if ($where) {
    $countSql .= ' WHERE ' . implode(' AND ', $where);
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalCount = (int)($countStmt->fetchColumn() ?? 0);

$sql = "SELECT * FROM {$table}";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

function gdy_comment_status_badge(string $status): string
{
    $status = strtolower(trim($status));

    $map = [
        'approved' => ['مقبول', '#dcfce7', '#166534'],
        'active'   => ['نشط', '#dbeafe', '#1d4ed8'],
        'pending'  => ['بانتظار المراجعة', '#fef3c7', '#92400e'],
        'spam'     => ['مزعج', '#fee2e2', '#991b1b'],
    ];

    [$label, $bg, $fg] = $map[$status] ?? [$status !== '' ? $status : 'غير محدد', '#e5e7eb', '#374151'];

    return '<span style="display:inline-block;padding:.28rem .55rem;border-radius:999px;background:' . h($bg) . ';color:' . h($fg) . ';font-size:.78rem;font-weight:700;">' . h($label) . '</span>';
}

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../layout/sidebar.php';
?>

<style>
.gdy-comments-page-card{
  background:#fff;
  border:1px solid #e5e7eb;
  border-radius:18px;
  overflow:hidden;
  box-shadow:0 10px 30px rgba(15,23,42,.06);
}
.gdy-comments-page-head{
  padding:18px 22px;
  background:linear-gradient(135deg,#0f172a,#1e293b);
  color:#fff;
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
  flex-wrap:wrap;
}
.gdy-comments-page-head h1{
  margin:0;
  font-size:1.15rem;
  font-weight:800;
}
.gdy-comments-page-head .meta{
  font-size:.9rem;
  opacity:.9;
}
.gdy-comments-toolbar{
  padding:16px 20px 6px;
}
.gdy-comments-toolbar form{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}
.gdy-comments-toolbar .form-control,
.gdy-comments-toolbar .form-select{
  max-width:280px;
}
.gdy-comments-body{
  padding:10px 20px 20px;
}
.gdy-comments-table-wrap{
  overflow:auto;
}
.gdy-comments-table{
  width:100%;
  min-width:900px;
  border-collapse:separate;
  border-spacing:0;
}
.gdy-comments-table th{
  background:#f8fafc;
  color:#0f172a;
  font-weight:800;
  border-bottom:1px solid #e5e7eb;
  padding:12px 10px;
  text-align:right;
  vertical-align:middle;
}
.gdy-comments-table td{
  padding:12px 10px;
  border-bottom:1px solid #eef2f7;
  vertical-align:top;
  background:#fff;
}
.gdy-comment-snippet{
  line-height:1.8;
  color:#0f172a;
  white-space:normal;
  word-break:break-word;
}
.gdy-comment-author{
  font-weight:800;
  color:#111827;
}
.gdy-comment-date{
  color:#64748b;
  font-size:.85rem;
  white-space:nowrap;
}
.gdy-comments-actions{
  display:flex;
  gap:6px;
  flex-wrap:wrap;
}
.gdy-comments-empty{
  padding:24px;
  text-align:center;
  color:#64748b;
  background:#f8fafc;
  border:1px dashed #cbd5e1;
  border-radius:14px;
}
.gdy-flash{
  margin:14px 20px 0;
  padding:12px 14px;
  border-radius:12px;
  font-size:.92rem;
}
.gdy-flash-success{
  background:#ecfdf5;
  color:#065f46;
  border:1px solid #a7f3d0;
}
.gdy-flash-error{
  background:#fef2f2;
  color:#991b1b;
  border:1px solid #fecaca;
}
</style>

<div class="admin-content">
  <div class="container-fluid" style="max-width:1280px;margin:24px auto;">

    <div class="gdy-comments-page-card">
      <div class="gdy-comments-page-head">
        <div>
          <h1>إدارة التعليقات</h1>
          <div class="meta">إجمالي النتائج: <?php echo (int)$totalCount; ?></div>
        </div>
        <div class="meta">
          الجدول المستخدم:
          <strong><?php echo h($table); ?></strong>
        </div>
      </div>

      <?php if ($flashSuccess !== ''): ?>
        <div class="gdy-flash gdy-flash-success"><?php echo h($flashSuccess); ?></div>
      <?php endif; ?>

      <?php if ($flashError !== ''): ?>
        <div class="gdy-flash gdy-flash-error"><?php echo h($flashError); ?></div>
      <?php endif; ?>

      <div class="gdy-comments-toolbar">
        <form method="get">
          <input
            type="text"
            name="q"
            value="<?php echo h($q); ?>"
            placeholder="بحث في الكاتب أو نص التعليق"
            class="form-control"
          >

          <select name="status" class="form-select">
            <option value="">كل الحالات</option>
            <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>approved</option>
            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>pending</option>
            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>active</option>
            <option value="spam" <?php echo $statusFilter === 'spam' ? 'selected' : ''; ?>>spam</option>
          </select>

          <button class="btn btn-primary" type="submit">بحث</button>

          <?php if ($q !== '' || $statusFilter !== ''): ?>
            <a class="btn btn-outline-secondary" href="index.php">إعادة ضبط</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="gdy-comments-body">
        <?php if ($rows): ?>
          <div class="gdy-comments-table-wrap">
            <table class="gdy-comments-table">
              <thead>
                <tr>
                  <th style="width:70px">#</th>
                  <th style="width:180px">الكاتب</th>
                  <th>التعليق</th>
                  <th style="width:130px">الحالة</th>
                  <th style="width:160px">التاريخ</th>
                  <th style="width:210px">إجراءات</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $row): ?>
                  <?php
                    $author = $table === 'news_comments'
                        ? (string)($row['name'] ?? '')
                        : (string)($row['author_name'] ?? '');

                    $body = $table === 'news_comments'
                        ? (string)($row['body'] ?? '')
                        : (string)($row['content'] ?? ($row['body'] ?? ''));

                    $status = (string)($row['status'] ?? '');
                    $createdAt = (string)($row['created_at'] ?? '');
                  ?>
                  <tr>
                    <td><?php echo (int)($row['id'] ?? 0); ?></td>

                    <td>
                      <div class="gdy-comment-author"><?php echo h($author !== '' ? $author : 'زائر'); ?></div>
                    </td>

                    <td>
                      <div class="gdy-comment-snippet">
                        <?php echo nl2br(h(mb_substr($body, 0, 500))); ?>
                      </div>
                    </td>

                    <td><?php echo gdy_comment_status_badge($status); ?></td>

                    <td>
                      <div class="gdy-comment-date"><?php echo h($createdAt); ?></div>
                    </td>

                    <td>
                      <div class="gdy-comments-actions">
                        <a
                          class="btn btn-sm btn-success"
                          href="moderate.php?table=<?php echo h($table); ?>&id=<?php echo (int)$row['id']; ?>&action=approve"
                        >قبول</a>

                        <a
                          class="btn btn-sm btn-warning"
                          href="moderate.php?table=<?php echo h($table); ?>&id=<?php echo (int)$row['id']; ?>&action=spam"
                        >مزعج</a>

                        <a
                          class="btn btn-sm btn-danger"
                          href="moderate.php?table=<?php echo h($table); ?>&id=<?php echo (int)$row['id']; ?>&action=delete"
                          onclick="return confirm('حذف التعليق؟')"
                        >حذف</a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="gdy-comments-empty">لا توجد تعليقات مطابقة حاليًا.</div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>