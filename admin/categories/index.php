<?php
require_once __DIR__ . '/../_admin_guard.php';

require_once __DIR__ . '/../../includes/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    gdy_session_start();
}

$pdo = gdy_pdo_safe();

$role = (string)($_SESSION['user']['role'] ?? '');
if (empty($_SESSION['user']['id']) || $role !== 'admin') {
    header('Location: ' .base_url('/login'));
    exit;
}

if (function_exists('h') === false) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (function_exists('generate_csrf_token') === false) {
    function generate_csrf_token(): string {
        if (empty($_SESSION['csrf_token']) === true) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
if (function_exists('verify_csrf_token') === false) {
    function verify_csrf_token(?string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
    }
}
$csrfToken = generate_csrf_token();

if (function_exists('slugify') === false) {
    function slugify(string $str): string {
        $str = trim($str);
        $str = preg_replace('/[^\p{Arabic}a-zA-Z0-9]+/u', '-', $str);
        $str = trim($str, '-');
        if ($str === '') {
            $str = 'cat-' .bin2hex(random_bytes(3));
        }
        return mb_strtolower($str, 'UTF-8');
    }
}

$currentPage = 'categories';

$errors = [];
$success = [];

$hasCatDescription = false;
$hasCatParent = false;
$hasCatMembersOnly = false;
if ($pdo instanceof PDO) {
    try {
        $colsStmt = gdy_db_stmt_columns($pdo, 'categories');
        $cols = (empty($colsStmt) === false) ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
        $hasCatDescription = in_array('description', $cols, true);
        $hasCatParent = in_array('parent_id', $cols, true);
        $hasCatMembersOnly = in_array('is_members_only', $cols, true);
    } catch (\Throwable $e) {
        error_log('[Admin Categories] categories schema check error: ' . $e->getMessage());
    }
}

$newsJoinMode = 'none'; 
if ($pdo instanceof PDO) {
    try {
        $stmtT = gdy_db_stmt_table_exists($pdo, 'news');
        $hasNewsTable = ($stmtT && $stmtT->fetchColumn()) ? true : false;

        if ($hasNewsTable) {
            $colsStmt = gdy_db_stmt_columns($pdo, 'news');
            $cols = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];

            if (in_array('category_id', $cols, true) === true) {
                $newsJoinMode = 'by_id';
            } elseif (in_array('category_slug', $cols, true) === true) {
                $newsJoinMode = 'by_slug';
            } else {
                $newsJoinMode = 'none';
                error_log('[Admin Categories] news table exists but no category_id/category_slug column found.');
            }
        }
    } catch (\Throwable $e) {
        error_log('[Admin Categories] news schema check error: ' . $e->getMessage());
        $newsJoinMode = 'none';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $errors[] = __('t_5cf4890bdd', 'رمز الحماية (CSRF) غير صالح، الرجاء تحديث الصفحة والمحاولة مرة أخرى.');
    } elseif (($pdo instanceof PDO) === false) {
        $errors[] = __('t_ed202270ee', 'قاعدة البيانات غير متاحة حالياً.');
    } else {
        try {
            if ($action === 'create') {
                $name = trim((string)($_POST['name'] ?? ''));
                $slug = trim((string)($_POST['slug'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== ''
                    ? (int)$_POST['parent_id']
                    : null;

                $isMembersOnly = ($hasCatMembersOnly && isset($_POST['is_members_only'])) ? 1 : 0;

                if ($name === '') {
                    $errors[] = __('t_f2f67ddb8a', 'اسم القسم مطلوب.');
                }

                if (empty($errors)) {
                    $slug = $slug === '' ? slugify($name) : slugify($slug);

                    $check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = :slug");
                    $check->execute(['slug' => $slug]);
                    if ((int)$check->fetchColumn() > 0) {
                        $errors[] = __('t_175f5f50cd', 'هذا المعرّف (slug) مستخدم بالفعل، الرجاء اختيار غيره.');
                    } else {
                        {
                            $cols = ['name','slug'];
                            $vals = [':name',':slug'];
                            $params = [
                                'name' => $name,
                                'slug' => $slug,
                            ];

                            if ($hasCatDescription) {
                                $cols[] = 'description';
                                $vals[] = ':description';
                                $params['description'] = $description;
                            }
                            if ($hasCatParent) {
                                $cols[] = 'parent_id';
                                $vals[] = ':parent_id';
                                $params['parent_id'] = $parentId;
                            }
                            if ($hasCatMembersOnly) {
                                $cols[] = 'is_members_only';
                                $vals[] = ':is_members_only';
                                $params['is_members_only'] = $isMembersOnly;
                            }

                            $sql = "INSERT INTO categories (" .implode(', ', $cols) . ")
                                    VALUES (" .implode(', ', $vals) . ")";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                        }
$success[] = __('t_904646a471', 'تم إضافة القسم بنجاح.');
                    }
                }

            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim((string)($_POST['name'] ?? ''));
                $slug = trim((string)($_POST['slug'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== ''
                    ? (int)$_POST['parent_id']
                    : null;
                $isMembersOnly = ($hasCatMembersOnly && isset($_POST['is_members_only'])) ? 1 : 0;

                if ($id <= 0) {
                    $errors[] = __('t_3655f75aec', 'معرّف القسم غير صالح.');
                }
                if ($name === '') {
                    $errors[] = __('t_f2f67ddb8a', 'اسم القسم مطلوب.');
                }

                if (empty($errors)) {
                    $slug = $slug === '' ? slugify($name) : slugify($slug);

                    $check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = :slug AND id != :id");
                    $check->execute([
                        'slug' => $slug,
                        'id' => $id,
                    ]);
                    if ((int)$check->fetchColumn() > 0) {
                        $errors[] = __('t_6f295d712b', 'هذا المعرّف (slug) مستخدم لقسم آخر.');
                    } else {
                        {
                        $sets = ["name = :name", "slug = :slug"];
                        $params = [
                            'id' => $id,
                            'name' => $name,
                            'slug' => $slug,
                        ];

                        if ($hasCatDescription) {
                            $sets[] = "description = :description";
                            $params['description'] = $description;
                        }
                        if ($hasCatParent) {
                            $sets[] = "parent_id = :parent_id";
                            $params['parent_id'] = $parentId;
                        }
                        if ($hasCatMembersOnly) {
                            $sets[] = "is_members_only = :is_members_only";
                            $params['is_members_only'] = $isMembersOnly;
                        }

                        $sql = "UPDATE categories SET " .implode(", ", $sets) . " WHERE id = :id";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                    }
$success[] = __('t_a3a05f91fb', 'تم تحديث القسم بنجاح.');
                    }
                }

            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $errors[] = __('t_3655f75aec', 'معرّف القسم غير صالح.');
                } else {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    $success[] = __('t_7f8f6b687c', 'تم حذف القسم بنجاح.');
                }
            }
        } catch (\Throwable $e) {
            error_log('[Admin Categories] action error: ' . $e->getMessage());
            $errors[] = __('t_a2db7a2af3', 'حدث خطأ أثناء معالجة الطلب، الرجاء المحاولة لاحقاً.');
        }
    }
}

$search = trim((string)($_GET['q'] ?? ''));

$categories = [];
$statsCats = [
    'total' => 0,
    'with_news' => 0,
    'empty' => 0,
    'root' => 0,
];

if ($pdo instanceof PDO) {
    try {
        $where = '1=1';
        $params = [];

        if ($search !== '') {
            $where .= " AND (c.name LIKE :q OR c.slug LIKE :q)";
            $params[':q'] = '%' . $search . '%';
        }

        if ($hasCatDescription) {
            $selectDesc = "c.description";
            $groupDesc = ", c.description";
        } else {
            $selectDesc = "'' AS description";
            $groupDesc = "";
        }

        $selectMembers = $hasCatMembersOnly ? "c.is_members_only" : "0 AS is_members_only";
        $groupMembers = $hasCatMembersOnly ? ", c.is_members_only" : "";

        if ($newsJoinMode === 'by_id') {
            $sql = "
                SELECT c .id, c .name, c .slug, {$selectDesc}, {$selectMembers}, c .parent_id,
                       (SELECT pc .name FROM categories pc WHERE pc .id = c .parent_id) AS parent_name,
                       COUNT(n .id) AS news_count
                FROM categories c
                LEFT JOIN news n ON n .category_id = c .id
                WHERE $where
                GROUP BY c .id, c .name, c .slug, c .parent_id{$groupDesc}{$groupMembers}
                ORDER BY c .id DESC
            ";
        } elseif ($newsJoinMode === 'by_slug') {
            $sql = "
                SELECT c .id, c .name, c .slug, {$selectDesc}, {$selectMembers}, c .parent_id,
                       (SELECT pc .name FROM categories pc WHERE pc .id = c .parent_id) AS parent_name,
                       COUNT(n .id) AS news_count
                FROM categories c
                LEFT JOIN news n ON n .category_slug = c .slug
                WHERE $where
                GROUP BY c .id, c .name, c .slug, c .parent_id{$groupDesc}{$groupMembers}
                ORDER BY c .id DESC
            ";
        } else {
            $sql = "
                SELECT c .id, c .name, c .slug, {$selectDesc}, {$selectMembers}, c .parent_id,
                       (SELECT pc .name FROM categories pc WHERE pc .id = c .parent_id) AS parent_name,
                       0 AS news_count
                FROM categories c
                WHERE $where
                ORDER BY c .id DESC
            ";
        }

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($categories as $cat) {
            $statsCats['total']++;
            $cnt = (int)($cat['news_count'] ?? 0);
            if ($cnt > 0) {
                $statsCats['with_news']++;
            } else {
                $statsCats['empty']++;
            }
            if (empty($cat['parent_id'])) {
                $statsCats['root']++;
            }
        }
    } catch (\Throwable $e) {
        error_log('[Admin Categories] list error: ' . $e->getMessage());
        $errors[] = __('t_8b5340d791', 'حدث خطأ أثناء جلب الأقسام.');
    }
}

$editId = isset($_GET['edit']) && ctype_digit((string)$_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId > 0 && $pdo instanceof PDO) {
    try {
        $sqlEdit = "SELECT id, name, slug";
        if ($hasCatDescription) {
            $sqlEdit .= ", description";
        }
        if ($hasCatParent) {
            $sqlEdit .= ", parent_id";
        }
        if ($hasCatMembersOnly) {
            $sqlEdit .= ", is_members_only";
        }
        $sqlEdit .= " FROM categories WHERE id = :id LIMIT 1";

        $stmt = $pdo->prepare($sqlEdit);
        $stmt->execute(['id' => $editId]);
        $editRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (\Throwable $e) {
        error_log('[Admin Categories] edit load error: ' . $e->getMessage());
    }
}

$pageTitle = __('t_0a83b235e0', 'إدارة الأقسام');

$script = $_SERVER['SCRIPT_NAME'] ?? '';
$adminBase = '';
if ($script !== '' && ($pos = strpos($script, '/admin/')) !== false) {
    $adminBase = substr($script, 0, $pos + strlen('/admin'));
} else {
    $adminBase = rtrim(dirname($script), '/');
}
$adminBase = rtrim((string)$adminBase, '/');

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<style nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>">
:root {
  --gdy-primary: 
  --gdy-accent: 
  --gdy-warning: 
  
  --gdy-shell-max: min(880px, calc(100vw - 360px));
}

html, body {
  overflow-x: hidden;
}

 .admin-content {
  max-width: var(--gdy-shell-max);
  width: 100%;
  margin: 0 auto;
  background: radial-gradient(circle at top left, 
  min-height: 100vh;
  color: 
  font-family: "Cairo", system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

 .admin-content .container-fluid {
  padding-top: 1rem;
  padding-bottom: 1rem;
}

 .gdy-layout-wrap {
  width: 100%;
}

 .gdy-page-header {
  padding: . 9rem 1.1rem . 8rem;
  margin-bottom: . 9rem;
  border-radius: 1rem;
  background: radial-gradient(circle at top, 
  border: 1px solid rgba(148,163,184,0.35);
  box-shadow: 0 8px 20px rgba(15,23,42,0.85);
  position: relative;
  overflow: hidden;
}
 .gdy-page-header::before {
  content: '';
  position: absolute;
  inset-inline-start: -40%;
  inset-block-start: -40%;
  width: 60%;
  height: 60%;
  background: radial-gradient(circle at top left, rgba(56,189,248,0.25), transparent 70%);
}
 .gdy-page-header-inner {
  position: relative;
  z-index: 1;
}
 .gdy-page-header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: . 75rem;
  flex-wrap: wrap;
}
 .gdy-page-kicker {
  display: inline-flex;
  align-items: center;
  gap: . 25rem;
  padding: . 12rem . 55rem;
  border-radius: 999px;
  font-size: . 7rem;
  background: rgba(15,23,42,0.9);
  color: 
  border: 1px solid rgba(148,163,184,0.5);
}
 .gdy-page-title {
  font-size: 1.1rem;
  font-weight: 700;
  margin: . 25rem 0 . 1rem;
  color: 
}
 .gdy-page-subtitle {
  font-size: . 8rem;
  color: 
  margin: 0;
}
 .gdy-page-header-actions {
  display: flex;
  align-items: center;
  gap: . 5rem;
  flex-wrap: wrap;
}
 .gdy-pill-meta {
  display: inline-flex;
  align-items: center;
  gap: . 35rem;
  padding: . 2rem . 6rem;
  border-radius: 999px;
  background: rgba(15,23,42,0.9);
  border: 1px solid rgba(148,163,184,0.6);
  font-size: . 73rem;
  color: 
}
 .gdy-pill-meta strong {
  font-weight: 700;
  color: 
}

 .gdy-page-header-filters {
  margin-top: . 45rem;
  padding-top: . 45rem;
  border-top: 1px solid rgba(31,41,55,0.85);
  display: flex;
  flex-wrap: wrap;
  gap: . 35rem;
}
 .gdy-chip-filter {
  font-size: . 74rem;
  padding: . 18rem . 55rem;
  border-radius: 999px;
  border: 1px solid rgba(148,163,184,0.6);
  background: rgba(15,23,42,0.9);
  color: 
  cursor: pointer;
  user-select: none;
  display: inline-flex;
  align-items: center;
  gap: . 25rem;
}
 .gdy-chip-filter i {
  font-size: . 7rem;
}
 .gdy-chip-filter .active {
  background: linear-gradient(135deg, 
  border-color: transparent;
  color: 
}

 .gdy-cats-stats-strip {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: . 6rem;
  margin-bottom: . 75rem;
}
 .gdy-cats-stat {
  position: relative;
  overflow: hidden;
  border-radius: . 85rem;
  padding: . 55rem . 75rem;
  background: radial-gradient(circle at top left, rgba(15,23,42,0.95), rgba(15,23,42,0.98));
  border: 1px solid rgba(148,163,184,0.4);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: . 55rem;
}
 .gdy-cats-stat::before {
  content: '';
  position: absolute;
  inset: -40%;
  background: radial-gradient(circle at top right, rgba(14,165,233,0.18), transparent 60%);
}
 .gdy-cats-stat-inner {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: . 55rem;
}
 .gdy-cats-stat-icon {
  width: 28px;
  height: 28px;
  border-radius: . 75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(15,23,42,0.9);
  font-size: . 85rem;
}
 .gdy-cats-stat-label {
  font-size: . 76rem;
  color: 
}
 .gdy-cats-stat-value {
  font-size: 1rem;
  font-weight: 700;
}
 .gdy-cats-stat-tag {
  position: relative;
  z-index: 1;
  font-size: . 73rem;
  padding: . 18rem . 45rem;
  border-radius: 999px;
  background: rgba(15,23,42,0.9);
}

 .gdy-card {
  border-radius: 1rem;
  border: 1px solid rgba(148,163,184,0.35);
  background: radial-gradient(circle at top .left, rgba(15,23,42,0.97), rgba(15,23,42,0.99));
  box-shadow: 0 16px 40px rgba(15,23,42,0.95);
  overflow: hidden;
}
 .gdy-card-header {
  padding: . 7rem . 9rem;
  border-bottom: 1px solid rgba(55,65,81,0.9);
  background: linear-gradient(135deg, 
  color: 
  font-size: . 9rem;
}

 .gdy-form-label {
  font-size: . 83rem;
  font-weight: 500;
}
 .gdy-input,
 .gdy-textarea {
  background-color: 
  border: 1px solid rgba(55,65,81,0.9);
  color: 
  font-size: . 85rem;
}
 .gdy-input:focus,
 .gdy-textarea:focus {
  border-color: var(--gdy-primary);
  box-shadow: 0 0 0 0.1rem rgba(14,165,233,0.25);
}
 .gdy-input::placeholder,
 .gdy-textarea::placeholder {
  color: 
  opacity: 1;
}
 .gdy-helper-text {
  font-size: . 73rem;
  color: 
}
 .gdy-desc-counter {
  font-size: . 73rem;
  color: 
  text-align: left;
}

 .table-cats {
  color: 
  font-size: . 84rem;
}
 .table-cats thead {
  background: rgba(15,23,42,1);
}
 .table-cats thead th {
  border-bottom: 1px solid rgba(55,65,81,0.9) !important;
  font-size: . 76rem;
  text-transform: uppercase;
  letter-spacing: . 05em;
}

 .table-cats th,
 .table-cats td {
  padding: . 22rem . 32rem;
}

 .table-cats thead th:nth-child(5),
 .table-cats tbody td .desc-cell {
  width: 40%;
}
 .table-cats thead th:nth-child(6),
 .table-cats tbody td .count-cell {
  width: 80px;
  text-align: center;
}
 .table-cats thead th:nth-child(7),
 .table-cats tbody td .actions-cell {
  width: 130px;
}

 .table-cats tbody tr {
  transition: background-color . 2s ease, transform . 1s .ease;
}
 .table-cats tbody tr:hover {
  background: rgba(15,23,42,0.95);
  transform: translateY(-1px);
}
 .table-cats tbody td {
  vertical-align: middle;
}

 .slug-cell code {
  max-width: 110px;
  display: inline-block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

 .badge-count {
  font-size: . 7rem;
  padding: . 16rem . 4rem;
  border-radius: 999px;
  background: rgba(15,23,42,0.9);
  border: 1px solid rgba(148,163,184,0.6);
}

 .btn-icon {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: . 72rem;
}

 .gdy-cat-filter-bar {
  margin-bottom: . 9rem;
  padding: . 6rem . 8rem;
  border-radius: 1rem;
  background: rgba(15,23,42,0.9);
  border: 1px solid rgba(31,41,55,0.9);
}

@media (max-width: 767.98px) {
  .admin-content {
    max-width: 100%;
  }
  .gdy-page-header {
    padding: . 7rem . 8rem;
  }
  .gdy-page-header-top {
    align-items: flex-start;
  }
}
</style>

<div class = "admin-content container-fluid py-4">
  <div class = "gdy-layout-wrap">

    <div class = "gdy-page-header">
      <div class = "gdy-page-header-inner">
        <div class = "gdy-page-header-top">
          <div>
            <div class = "gdy-page-kicker">
              <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
              <?php echo h(__('t_9efc061dd5', 'لوحة المحتوى')); ?>
            </div>
            <h1 class = "gdy-page-title"><?php echo h($pageTitle); ?></h1>
            <p class = "gdy-page-subtitle">
              <?php echo h(__('t_cee7f4ea6d', 'إدارة أقسام الأخبار وتنظيم بنية المحتوى، مع إمكانيات الفلترة السريعة.')); ?>
            </p>
          </div>
          <div class = "gdy-page-header-actions">
            <div class = "gdy-pill-meta">
              <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
              <span><?php echo h(__('t_cbb2bd24d9', 'إجمالي الأقسام:')); ?> <strong><?php echo number_format($statsCats['total']); ?></strong></span>
            </div>
            <a href = "#category-form" class = "btn btn-sm btn-primary">
              <svg class = "gdy-icon me-1" aria-hidden = "true" focusable = "false"><use href = "#plus"></use></svg> <?php echo h(__('t_27535dee80', 'قسم جديد')); ?>
            </a>
          </div>
        </div>

        <div class = "gdy-page-header-filters">
          <div class = "gdy-chip-filter active" data-filter-cats = "all">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg> <?php echo h(__('t_6d08f19681', 'الكل')); ?>
          </div>
          <div class = "gdy-chip-filter" data-filter-cats = "root">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg> الأقسام الرئيسية فقط (<?php echo number_format($statsCats['root']); ?>)
          </div>
          <div class = "gdy-chip-filter" data-filter-cats = "with-news">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#news"></use></svg> أقسام تحتوي أخبار (<?php echo number_format($statsCats['with_news']); ?>)
          </div>
          <div class = "gdy-chip-filter" data-filter-cats = "empty">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg> أقسام بدون أخبار (<?php echo number_format($statsCats['empty']); ?>)
          </div>
        </div>
      </div>
    </div>

    <!-- كروت الإحصائيات -->
    <div class = "gdy-cats-stats-strip">
      <div class = "gdy-cats-stat">
        <div class = "gdy-cats-stat-inner">
          <div class = "gdy-cats-stat-icon text-info">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
          </div>
          <div>
            <div class = "gdy-cats-stat-label"><?php echo h(__('t_47254d4799', 'إجمالي الأقسام')); ?></div>
            <div class = "gdy-cats-stat-value"><?php echo number_format($statsCats['total']); ?></div>
          </div>
        </div>
        <div class = "gdy-cats-stat-tag"><?php echo h(__('t_6b7d9b3237', 'كل الأقسام')); ?></div>
      </div>

      <div class = "gdy-cats-stat">
        <div class = "gdy-cats-stat-inner">
          <div class = "gdy-cats-stat-icon text-success">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#news"></use></svg>
          </div>
          <div>
            <div class = "gdy-cats-stat-label"><?php echo h(__('t_a932911686', 'أقسام تحتوي أخبار')); ?></div>
            <div class = "gdy-cats-stat-value"><?php echo number_format($statsCats['with_news']); ?></div>
          </div>
        </div>
        <div class = "gdy-cats-stat-tag"><?php echo h(__('t_2c7cd087dc', 'نشطة')); ?></div>
      </div>

      <div class = "gdy-cats-stat">
        <div class = "gdy-cats-stat-inner">
          <div class = "gdy-cats-stat-icon text-warning">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
          </div>
          <div>
            <div class = "gdy-cats-stat-label"><?php echo h(__('t_3480ddb507', 'أقسام بدون أخبار')); ?></div>
            <div class = "gdy-cats-stat-value"><?php echo number_format($statsCats['empty']); ?></div>
          </div>
        </div>
        <div class = "gdy-cats-stat-tag"><?php echo h(__('t_225dc4b4aa', 'يمكن مراجعتها')); ?></div>
      </div>
    </div>

    <?php if (!empty($success)): ?>
      <div class = "alert alert-success alert-dismissible fade show" role = "alert">
        <ul class = "mb-0">
          <?php foreach ($success as $msg): ?>
            <li><?php echo h($msg); ?></li>
          <?php endforeach; ?>
        </ul>
        <button type = "button" class = "btn-close" data-bs-dismiss = "alert" aria-label = "Close"></button>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class = "alert alert-danger alert-dismissible fade show" role = "alert">
        <ul class = "mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo h($err); ?></li>
          <?php endforeach; ?>
        </ul>
        <button type = "button" class = "btn-close" data-bs-dismiss = "alert" aria-label = "Close"></button>
      </div>
    <?php endif; ?>

    <!-- شريط البحث -->
    <div class = "gdy-cat-filter-bar mb-3">
      <form class = "row g-2 align-items-center" method = "get" action = "">
        <div class = "col-sm-6 col-md-4 col-lg-3">
          <input
            type = "text"
            name = "q"
            value = "<?php echo h($search); ?>"
            class = "form-control form-control-sm bg-dark border-secondary text-light"
            placeholder = "<?php echo h(__('t_d784e72674', 'بحث في اسم القسم أو المعرّف (slug)...')); ?>"
          >
        </div>
        <div class = "col-auto">
          <button type = "submit" class = "btn btn-outline-secondary btn-sm">
            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#search"></use></svg> <?php echo h(__('t_ab79fc1485', 'بحث')); ?>
          </button>
        </div>
        <div class = "col-auto ms-auto text-end small text-muted d-none d-md-block">
          <span><?php echo h(__('t_5501d9f909', 'عدد الأقسام المعروضة:')); ?> <strong><?php echo number_format($statsCats['total']); ?></strong></span>
        </div>
      </form>
    </div>

    <?php
      
      require_once __DIR__ . '/../includes/saved_filters_ui.php';
          echo gdy_saved_filters_ui('categories');
?>

<?php
  
  $allCatsForParent = [];
  $currentParentId = (int)($editRow['parent_id'] ?? 0);
  if ($pdo instanceof PDO && $hasCatParent) {
      try {
          $stParent = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
          $allCatsForParent = $stParent ? $stParent->fetchAll(PDO::FETCH_ASSOC) : [];
      } catch (\Throwable $e) {
          error_log('[Admin Categories] parent categories load error: ' . $e->getMessage());
      }
  }
?>

    <!-- كرت إضافة / تعديل قسم (أفقي بعرض كامل) -->
    <div id = "category-form" class = "mb-4">
      <div class = "card gdy-card">
        <div class = "gdy-card-header">
          <strong><?php echo $editRow ? __('t_101b85caad', 'تعديل قسم') : __('t_327a5f706a', 'إضافة قسم جديد'); ?></strong>
        </div>
        <div class = "card-body">
          <form method = "post" action = "">
            <input type = "hidden" name = "csrf_token" value = "<?php echo h($csrfToken); ?>">
            <?php if ($editRow): ?>
              <input type = "hidden" name = "action" value = "update">
              <input type = "hidden" name = "id" value = "<?php echo (int)$editRow['id']; ?>">
            <?php else: ?>
              <input type = "hidden" name = "action" value = "create">
            <?php endif; ?>

            <div class = "row g-3">
              <div class = "col-md-6">
                <label class = "form-label gdy-form-label"><?php echo h(__('t_c80354bf07', 'اسم القسم')); ?> <span class = "text-danger">*</span></label>
                <input type = "text" name = "name" id = "catNameInput" class = "form-control gdy-input"
                       value = "<?php echo h($editRow['name'] ?? ''); ?>" required
                       placeholder = "<?php echo h(__('t_78ecdc29c0', 'مثال: أخبار محلية')); ?>">
                <div class = "gdy-helper-text mt-1">
                  <?php echo h(__('t_2d38f811b3', 'الاسم الظاهر للزوار في القوائم والصفحات.')); ?>
                </div>
              </div>

              <div class = "col-md-6">
                <label class = "form-label gdy-form-label"><?php echo h(__('t_3f56539ded', 'المعرّف (Slug)')); ?></label>
                <input type = "text" name = "slug" id = "catSlugInput" class = "form-control gdy-input"
                       value = "<?php echo h($editRow['slug'] ?? ''); ?>"
                       placeholder = "<?php echo h(__('t_e6468d6b68', 'يُفضّل أن يكون قصيراً بدون مسافات')); ?>">
                <div class = "gdy-helper-text.mt-1">
                  <?php echo h(__('t_39182e3e04', 'إذا تركته فارغاً سيتم توليده تلقائياً من اسم القسم.')); ?>
                </div>
              </div>
            </div>

            <div class = "mt-3">
              <label class = "form-label gdy-form-label"><?php echo h(__('t_2db04ee88f', 'وصف القسم')); ?></label>
              <textarea name = "description" id = "catDescInput"
                        class = "form-control gdy-textarea" rows = "3"
                        placeholder = "<?php echo h(__('t_8616cfa7ab', 'وصف قصير يساعد في فهم محتوى هذا القسم...')); ?>"><?php echo h($editRow['description'] ?? ''); ?></textarea>
              <div class = "d-flex justify-content-between mt-1">
                <small class = "gdy-helper-text">
                  <?php echo h(__('t_fa0a7f9e07', 'اختياري، مفيد لـ SEO وللفريق التحريري.')); ?>
                </small>
                <small class = "gdy-desc-counter">
                  <span id = "catDescCount">0</span> / 160
                </small>
              </div>
              <?php if (!$hasCatDescription): ?>
                <small class = "text-warning d-block mt-1">
                  <?php echo h(__('t_01cef970b2', 'ملاحظة: جدول الأقسام لا يحتوي عمود')); ?> <code>description</code> <?php echo h(__('t_64fdf67bff', 'حالياً.')); ?>
                </small>
              <?php endif; ?>
            </div>

            <?php if ($hasCatMembersOnly): ?>
              <div class = "form-check mt-3">
                <input class = "form-check-input" type = "checkbox" name = "is_members_only" id = "catMembersOnly" value = "1"
                       <?php echo !empty($editRow['is_members_only']) ? 'checked' : ''; ?>>
                <label class = "form-check-label" for = "catMembersOnly">
                  <svg class = "gdy-icon me-1" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg><?php echo h(__('قسم للأعضاء فقط')); ?>
                </label>
                <div class = "form-text"><?php echo h(__('سيظل القسم ظاهرًا للزوار، لكن المقالات ستظهر بعلامة 🔒 وعند فتحها يظهر Paywall.')); ?></div>
              </div>
            <?php endif; ?>

            <?php if ($hasCatParent): ?>
            <div class = "mt-3">
              <label class = "form-label gdy-form-label"><?php echo h(__('t_8816237320', 'القسم الأب (اختياري)')); ?></label>
              <select name = "parent_id" class = "form-select gdy-input">
                <option value = ""><?php echo h(__('t_2a5b37708d', 'بدون قسم أب (قسم رئيسي)')); ?></option>
                <?php foreach ($allCatsForParent as $pc): ?>
                  <?php if (!empty($editRow['id']) && (int)$editRow['id'] === (int)$pc['id']) continue; ?>
                  <option value = "<?php echo (int)$pc['id']; ?>"
                    <?php echo $currentParentId === (int)$pc['id'] ? 'selected' : ''; ?>>
                    <?php echo h($pc['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class = "gdy-helper-text mt-1">
                <?php echo h(__('t_4e5b2bd8c7', 'لإنشاء بنية متدرجة للأقسام (رئيسي ← فرعي).')); ?>
              </div>
            </div>
            <?php endif; ?>

            <div class = "d-flex justify-content-between mt-3">
              <?php if ($editRow): ?>
                <a href = "<?php echo h($adminBase); ?>/categories/index.php" class = "btn btn-outline-secondary.btn-sm">
                  <?php echo h(__('t_c5f601750e', 'إلغاء التعديل')); ?>
                </a>
                <button type = "submit" class = "btn btn-primary btn-sm">
                  <?php echo h(__('t_02f31ae27c', 'حفظ التغييرات')); ?>
                </button>
              <?php else: ?>
                <span></span>
                <button type = "submit" class = "btn btn-primary btn-sm">
                  <?php echo h(__('t_81d9a5642d', 'حفظ القسم')); ?>
                </button>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- كرت قائمة الأقسام (أسفل إضافة قسم جديد) -->
    <div class = "mb-4">
      <div class = "card gdy-card">
        <div class = "gdy-card-header">
          <strong><?php echo h(__('t_3e9a70c934', 'قائمة الأقسام')); ?></strong>
        </div>
        <div class = "card-body p-0">
          <?php if (!empty($categories)): ?>
            <div class = "table-responsive">
              <table class = "table table-hover mb-0 align-middle table-cats" id = "categoriesTable">
                <thead>
                  <tr>
                    <th style = "width: 50px;">#</th>
                    <th><?php echo h(__('t_c80354bf07', 'اسم القسم')); ?></th>
                    <th><?php echo h(__('t_4d27fe2ed2', 'القسم الأب')); ?></th>
                    <th>Slug</th>
                    <th><?php echo h(__('t_f58d38d563', 'الوصف')); ?></th>
                    <th><?php echo h(__('t_a1d2f1590c', 'عدد الأخبار')); ?></th>
                    <th><?php echo h(__('t_901efe9b1c', 'إجراءات')); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($categories as $cat): ?>
                    <?php
                      $id = (int)$cat['id'];
                      $name = (string)$cat['name'];
                      $slug = (string)$cat['slug'];
                      $desc = (string)($cat['description'] ?? '');
                      $cut = mb_substr($desc, 0, 60, 'UTF-8');
                      $countN = (int)($cat['news_count'] ?? 0);
                      $parentName = (string)($cat['parent_name'] ?? '');
                      $isRoot = ($parentName === '');
                      $hasNews = ($countN > 0);
                    ?>
                    <tr
                      data-cat-root = "<?php echo $isRoot ? '1' : '0'; ?>"
                      data-cat-has-news = "<?php echo $hasNews ? '1' : '0'; ?>"
                    >
                      <td><?php echo $id; ?></td>
                      <td class = "fw-semibold">
                        <?php echo h($name); ?>
                        <?php if ($hasCatMembersOnly && !empty($cat['is_members_only'])): ?>
                          <span class = "badge bg-dark-subtle text-dark ms-1" title = "للأعضاء فقط"><svg class = "gdy-icon me-1" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>للأعضاء</span>
                        <?php endif; ?>
                      </td>
                      <td class = "text-muted small">
                        <?php if ($parentName !== ''): ?>
                          <?php echo h($parentName); ?>
                        <?php else: ?>
                          <span class = "badge bg-secondary-subtle text-secondary"><?php echo h(__('t_9b71b13661', 'قسم رئيسي')); ?></span>
                        <?php endif; ?>
                      </td>
                      <td class = "slug-cell">
                        <code><?php echo h($slug); ?></code>
                      </td>
                      <td class = "desc-cell">
                        <?php echo h($cut) . (mb_strlen($desc, 'UTF-8') > 60 ? '…' : ''); ?>
                      </td>
                      <td class = "count-cell">
                        <span class = "badge-count">
                          <svg class = "gdy-icon me-1" aria-hidden = "true" focusable = "false"><use href = "#news"></use></svg><?php echo $countN; ?>
                        </span>
                      </td>
                      <td class = "actions-cell">
                        <div class = "d-flex justify-content-center gap-1 flex-wrap">
                          <a href = "<?php echo h($adminBase); ?>/categories/index.php?edit=<?php echo $id; ?>"
                             class = "btn btn-outline-primary btn-sm btn-icon"
                             title = "<?php echo h(__('t_fe87fab237', 'تعديل القسم')); ?>">
                            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                          </a>
                          <button
                            type = "button"
                            class = "btn btn-outline-secondary btn-sm btn-icon btn-copy-slug"
                            data-slug = "<?php echo h($slug); ?>"
                            title = "<?php echo h(__('t_b1e8292d82', 'نسخ المعرّف (slug)')); ?>">
                            <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                          </button>
                          <form method = "post" action = "" class = "d-inline"
                                data-confirm = 'هل أنت متأكد من حذف هذا القسم؟'>
                            <input type = "hidden" name = "csrf_token" value = "<?php echo h($csrfToken); ?>">
                            <input type = "hidden" name = "action" value = "delete">
                            <input type = "hidden" name = "id" value = "<?php echo $id; ?>">
                            <button type = "submit" class = "btn btn-outline-danger btn-sm btn-icon" title = "<?php echo h(__('t_a8bbe1197c', 'حذف القسم')); ?>">
                              <svg class = "gdy-icon" aria-hidden = "true" focusable = "false"><use href = "#more-h"></use></svg>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class = "p-3 text-muted">
              <?php echo h(__('t_ca213e8c09', 'لا توجد أقسام بعد. قم بإضافة أول قسم من النموذج أعلاه.')); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div><!-- /gdy-layout-wrap -->
</div><!-- /admin-content -->

<script nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>">
document .addEventListener('DOMContentLoaded', function () {
  
  document .querySelectorAll('.btn-copy-slug') .forEach(function (btn) {
    btn .addEventListener('click', function (e) {
      e .preventDefault();
      var slug = this .getAttribute('data-slug') || '';
      if (!slug) return;

      if (navigator .clipboard && navigator .clipboard .writeText) {
        navigator .clipboard .writeText(slug) .then(function () {
          showCopyToast('تم نسخ المعرّف (slug) بنجاح');
        }) .catch(function () {
          alert('تمت محاولة النسخ، لكن قد لا يكون مدعوماً في هذا المتصفح.');
        });
      } else {
        var tmp = document .createElement('input');
        tmp .value = slug;
        document .body .appendChild(tmp);
        tmp .select();
        document .execCommand('copy');
        document .body .removeChild(tmp);
        alert('تم نسخ المعرّف (slug) إلى الحافظة');
      }
    });
  });

  
  var filterChips = document .querySelectorAll('.gdy-chip-filter');
  var rows = document .querySelectorAll('#categoriesTable tbody tr');

  function applyFilter(filter) {
    rows .forEach(function (row) {
      var isRoot = row .getAttribute('data-cat-root') === '1';
      var hasNews = row .getAttribute('data-cat-has-news') === '1';
      var show = true;

      if (filter === 'root') {
        show = isRoot;
      } else if (filter === 'with-news') {
        show = hasNews;
      } else if (filter === 'empty') {
        show = !hasNews;
      }

      row .style .display = show ? '' : 'none';
    });
  }

  filterChips .forEach(function (chip) {
    chip .addEventListener('click', function () {
      filterChips .forEach(function (c) { c .classList .remove('active'); });
      this .classList .add('active');
      var filter = this .getAttribute('data-filter-cats') || 'all';
      applyFilter(filter);
    });
  });

  
  var nameInput = document .getElementById('catNameInput');
  var slugInput = document .getElementById('catSlugInput');
  var slugTouched = false;

  if (slugInput) {
    slugInput .addEventListener('input', function () {
      if (this .value .trim() !== '') {
        slugTouched = true;
      }
    });
  }

  function simpleSlugify(text) {
    text = text .trim();
    text = text .replace(/[^\u0600-\u06FFa-zA-Z0-9]+/g, '-');
    text = text .replace(/^-+ | -+$/g, '');
    return text .toLowerCase();
  }

  if (nameInput && slugInput) {
    nameInput .addEventListener('input', function () {
      if (!slugTouched && slugInput .value .trim() === '') {
        slugInput .value = simpleSlugify(this .value);
      }
    });
  }

  
  var descInput = document .getElementById('catDescInput');
  var descCount = document .getElementById('catDescCount');
  var descMax = 160;

  function updateDescCounter() {
    if (!descInput || !descCount) return;
    var len = descInput .value .length;
    descCount .textContent = len;
    descCount .style .color = len > descMax ? '#f97316' : '#9ca3af';
  }
  if (descInput) {
    descInput .addEventListener('input', updateDescCounter);
    updateDescCounter();
  }

  function showCopyToast(msg) {
    var toast = document .createElement('div');
    toast .textContent = msg;
    toast .style .position = 'fixed';
    toast .style .bottom = '20px';
    toast .style .left = '50%';
    toast .style .transform = 'translateX(-50%)';
    toast .style .background = 'rgba(15,23,42,0.96)';
    toast .style .color = '#e5e7eb';
    toast .style .padding = '8px 16px';
    toast .style .borderRadius = '999px';
    toast .style .fontSize = '13px';
    toast .style .zIndex = '9999';
    toast .style .border = '1px solid rgba(148,163,184,0.5)';
    document .body .appendChild(toast);
    setTimeout(function () {
      toast .remove();
    }, 2000);
  }
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
