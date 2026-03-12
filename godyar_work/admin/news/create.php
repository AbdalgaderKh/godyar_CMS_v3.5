<?php
require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/_news_helpers.php';

use Godyar\Auth;

if (!Auth::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

Auth::requirePermission('posts.create');

$isWriter = Auth::isWriter();
$userId = (int)($_SESSION['user']['id'] ?? 0);

$currentPage = 'posts';
$pageTitle = __('t_0d1f6ecf66', 'إضافة خبر جديد');

$pdo = gdy_pdo_safe();
if (!($pdo instanceof PDO)) {
    die('Database not available');
}

if (!function_exists('h')) {
    function h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('gdy_unique_news_slug')) {
    function gdy_unique_news_slug(PDO $pdo, string $baseSlug, ?int $excludeId = null): string
    {
        $baseSlug = trim($baseSlug);
        if ($baseSlug === '') {
            $baseSlug = 'news';
        }

        $slug = $baseSlug;
        $i = 2;

        while (true) {
            if ($excludeId !== null) {
                $st = $pdo->prepare('SELECT 1 FROM news WHERE slug = ? AND id <> ? LIMIT 1');
                $st->execute([$slug, $excludeId]);
            } else {
                $st = $pdo->prepare('SELECT 1 FROM news WHERE slug = ? LIMIT 1');
                $st->execute([$slug]);
            }

            if ($st->fetchColumn() === false) {
                return $slug;
            }

            $slug = $baseSlug . '-' . $i;
            $i++;

            if ($i > 2000) {
                return $baseSlug . '-' . time();
            }
        }
    }
}

$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (\Throwable $e) {
    error_log('Error fetching categories: ' . $e->getMessage());
}

$opinionAuthors = [];
try {
    if (function_exists('gdy_db_table_exists') && gdy_db_table_exists($pdo, 'opinion_authors')) {
        $stmt = $pdo->query("SELECT id, name FROM opinion_authors ORDER BY name ASC");
        $opinionAuthors = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (\Throwable $e) {
    error_log('Error fetching opinion_authors: ' . $e->getMessage());
}

$errors = [];
$title = '';
$slug = '';
$excerpt = '';
$content = '';
$publish_at = '';
$unpublish_at = '';
$publish_at_db = null;
$unpublish_at_db = null;
$seo_title = '';
$seo_description = '';
$seo_keywords = '';
$tags = '';
$status = $isWriter ? 'pending' : 'draft';
$category_id = 0;
$author_id = $userId;
$opinion_author_id = 0;
$featured = 0;
$is_breaking = 0;
$published_at = '';
$imagePath = null;
$image_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (function_exists('verify_csrf')) {
        try {
            verify_csrf();
        } catch (\Throwable $e) {
            if (function_exists('gdy_security_log')) {
                gdy_security_log('csrf_failed', ['file' => __FILE__]);
            }
            $_SESSION['error_message'] = $_SESSION['error_message'] ?? 'انتهت صلاحية الجلسة، يرجى إعادة المحاولة.';
            header('Location: ' . ($_SERVER['REQUEST_URI'] ?? 'index.php'));
            exit;
        }
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $slug = trim((string)($_POST['slug'] ?? ''));
    $excerpt = trim((string)($_POST['excerpt'] ?? ''));
    $content = (string)($_POST['content'] ?? '');

    $stripFormatting = (int)($_POST['strip_formatting'] ?? 0) === 1;
    $stripBg = (int)($_POST['strip_bg'] ?? 0) === 1;

    if ($stripBg) {
        $content = preg_replace("/\\sbgcolor\\s*=\\s*(['\"]).*?\\1/i", '', $content);
        $content = preg_replace('/background-color\s*:\s*[^;"]+;?/i', '', $content);
    }

    if ($stripFormatting) {
        $content = preg_replace("/\\sstyle\\s*=\\s*(['\"]).*?\\1/i", '', $content);
        $content = preg_replace("/\\sclass\\s*=\\s*(['\"]).*?\\1/i", '', $content);
        $content = preg_replace('/<\/?font[^>]*>/i', '', $content);
    }

    $publish_at = trim((string)($_POST['publish_at'] ?? ''));
    $publish_at_db = function_exists('gdy_dt_local_to_sql') ? gdy_dt_local_to_sql($publish_at) : ($publish_at !== '' ? $publish_at : null);

    $unpublish_at = trim((string)($_POST['unpublish_at'] ?? ''));
    $unpublish_at_db = function_exists('gdy_dt_local_to_sql') ? gdy_dt_local_to_sql($unpublish_at) : ($unpublish_at !== '' ? $unpublish_at : null);

    $seo_title = trim((string)($_POST['seo_title'] ?? ''));
    $seo_description = trim((string)($_POST['seo_description'] ?? ''));
    $seo_keywords = trim((string)($_POST['seo_keywords'] ?? ''));

    $tags = trim((string)($_POST['tags'] ?? ''));
    $tags_input = $tags;

    $status = trim((string)($_POST['status'] ?? 'published'));
    $category_id = (int)($_POST['category_id'] ?? 0);
    $author_id = $userId;
    $opinion_author_id = (int)($_POST['opinion_author_id'] ?? 0);
    $published_at = trim((string)($_POST['published_at'] ?? ''));
    $featured = isset($_POST['featured']) ? 1 : 0;
    $is_breaking = isset($_POST['is_breaking']) ? 1 : 0;
    $image_url = trim((string)($_POST['image_url'] ?? ''));

    if ($isWriter) {
        $featured = 0;
        $is_breaking = 0;
    }

    if ($title === '') {
        $errors['title'] = __('t_a177201400', 'يرجى إدخال عنوان الخبر.');
    }

    if ($slug === '' && $title !== '') {
        $slug = $title;
    }

    if ($slug !== '') {
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug);
        $slug = trim((string)$slug, '-');
        $slug = function_exists('mb_strtolower') ? mb_strtolower($slug, 'UTF-8') : strtolower($slug);
    }

    if ($slug === '') {
        $slug = 'news-' . date('YmdHis') . '-' . random_int(100, 999);
    }

    $slug = gdy_unique_news_slug($pdo, $slug);

    if ($category_id <= 0) {
        $errors['category_id'] = __('t_96bde08b29', 'يرجى اختيار التصنيف.');
    }

    $allowedStatuses = $isWriter ? ['draft', 'pending'] : ['published', 'draft', 'pending', 'approved', 'archived'];
    if (!in_array($status, $allowedStatuses, true)) {
        $status = $isWriter ? 'pending' : 'draft';
    }

    if ($isWriter && $status === 'published') {
        $status = 'pending';
    }

    $publishedAtForDb = function_exists('gdy_dt_local_to_sql') ? gdy_dt_local_to_sql($published_at) : ($published_at !== '' ? $published_at : null);

    if ($seo_title === '' && $title !== '') {
        $seo_title = mb_substr($title, 0, 60);
    }

    if ($seo_description === '') {
        $plain = trim((string)preg_replace('/\s+/', ' ', strip_tags($content)));
        $seo_description = mb_substr($plain !== '' ? $plain : $title, 0, 160);
    }

    if ($seo_keywords === '') {
        $plain = trim((string)preg_replace('/\s+/', ' ', strip_tags($content)));
        $seo_keywords = mb_substr(trim($title . ' ' . $plain), 0, 180);
    }

    if ($tags === '') {
        $words = preg_split('/\s+/', trim($title)) ?: [];
        $words = array_values(array_filter($words, static fn($w) => mb_strlen($w) >= 3));
        $tags = implode(', ', array_slice($words, 0, 6));
    }

    if (!$errors && $image_url !== '') {
        $imagePath = ltrim($image_url, '/');
    }

    if (!$errors && isset($_FILES['image']) && is_array($_FILES['image'])) {
        $err = (int)($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_NO_FILE) {
            if ($err !== UPLOAD_ERR_OK) {
                $errors['image'] = __('t_14a4dd5d81', 'حدث خطأ أثناء رفع الصورة. حاول مرة أخرى.');
            } else {
                require_once __DIR__ . '/../../includes/classes/SafeUploader.php';
                $SafeUploaderClass = 'Godyar' . chr(92) . 'SafeUploader';

                $destAbs = rtrim((string)ROOT_PATH, '/') . '/uploads/news';
                $urlPrefix = '/uploads/news';
                $maxSize = 5 * 1024 * 1024;

                if (!is_dir($destAbs)) {
                    if (function_exists('gdy_mkdir')) {
                        gdy_mkdir($destAbs, 0755, true);
                    } else {
                        @mkdir($destAbs, 0755, true);
                    }
                }

                $ht = $destAbs . '/.htaccess';
                if (is_dir($destAbs) && !is_file($ht)) {
                    $rules = <<<'HTACCESS'
Options -Indexes
<FilesMatch "\.(php|phtml|phar)$">
  Require all denied
</FilesMatch>
HTACCESS;
                    @file_put_contents($ht, $rules);
                }

                $res = $SafeUploaderClass::upload($_FILES['image'], [
                    'dest_abs_dir' => $destAbs,
                    'url_prefix' => $urlPrefix,
                    'max_bytes' => $maxSize,
                    'allowed_ext' => ['jpg','jpeg','png','gif','webp'],
                    'allowed_mime' => [
                        'jpg' => ['image/jpeg'],
                        'jpeg' => ['image/jpeg'],
                        'png' => ['image/png'],
                        'gif' => ['image/gif'],
                        'webp' => ['image/webp'],
                    ],
                    'prefix' => 'news_',
                ]);

                if (($res['success'] ?? false) === true) {
                    $imagePath = ltrim((string)$res['rel_url'], '/');
                } else {
                    $errors['image'] = (string)($res['error'] ?? __('t_6b9a5a9ac9', 'تعذر حفظ ملف الصورة على الخادم.'));
                }
            }
        }
    }

    if (!$errors) {
        try {
            $newsCols = function_exists('gdy_db_columns') ? gdy_db_columns($pdo, 'news') : [];

            if (($status === 'published' || $status === 'publish') && isset($newsCols['published_at']) && $publishedAtForDb === null) {
                $publishedAtForDb = date('Y-m-d H:i:s');
            }

            $columns = [];
            $placeholders = [];

            $add = static function(string $col, string $ph) use (&$columns, &$placeholders): void {
                $columns[] = $col;
                $placeholders[] = $ph;
            };

            $add('title', ':title');
            $add('slug', ':slug');

            if (isset($newsCols['excerpt'])) {
                $add('excerpt', ':excerpt');
            } elseif (isset($newsCols['summary'])) {
                $add('summary', ':excerpt');
            }

            if (isset($newsCols['content'])) {
                $add('content', ':content');
            } elseif (isset($newsCols['body'])) {
                $add('body', ':content');
            }

            if (isset($newsCols['category_id'])) $add('category_id', ':category_id');
            if (isset($newsCols['author_id'])) $add('author_id', ':author_id');
            if (isset($newsCols['opinion_author_id'])) $add('opinion_author_id', ':opinion_author_id');
            if (isset($newsCols['status'])) $add('status', ':status');
            if (isset($newsCols['featured'])) $add('featured', ':featured');
            if (isset($newsCols['is_breaking'])) $add('is_breaking', ':is_breaking');
            if (isset($newsCols['published_at'])) $add('published_at', ':published_at');
            if (isset($newsCols['publish_at'])) $add('publish_at', ':publish_at');
            if (isset($newsCols['unpublish_at'])) $add('unpublish_at', ':unpublish_at');
            if (isset($newsCols['seo_title'])) $add('seo_title', ':seo_title');
            if (isset($newsCols['seo_description'])) $add('seo_description', ':seo_description');
            if (isset($newsCols['seo_keywords'])) $add('seo_keywords', ':seo_keywords');

            $imageCols = [];
            foreach (['featured_image', 'image_path', 'image'] as $ic) {
                if (isset($newsCols[$ic])) {
                    $imageCols[] = $ic;
                    $add($ic, ':' . $ic);
                }
            }

            if (isset($newsCols['created_at'])) {
                $columns[] = 'created_at';
                $placeholders[] = 'NOW()';
            }

            $sql = 'INSERT INTO news (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);

            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);

            if (in_array('excerpt', $columns, true) || in_array('summary', $columns, true)) {
                $stmt->bindValue(':excerpt', $excerpt, PDO::PARAM_STR);
            }

            if (in_array('content', $columns, true) || in_array('body', $columns, true)) {
                $stmt->bindValue(':content', $content, PDO::PARAM_STR);
            }

            if (in_array('category_id', $columns, true)) {
                $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            }

            if (in_array('author_id', $columns, true)) {
                $stmt->bindValue(':author_id', $author_id, PDO::PARAM_INT);
            }

            if (in_array('opinion_author_id', $columns, true)) {
                if ($opinion_author_id > 0) {
                    $stmt->bindValue(':opinion_author_id', $opinion_author_id, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':opinion_author_id', null, PDO::PARAM_NULL);
                }
            }

            if (in_array('status', $columns, true)) {
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            }

            if (in_array('featured', $columns, true)) {
                $stmt->bindValue(':featured', $featured, PDO::PARAM_INT);
            }

            if (in_array('is_breaking', $columns, true)) {
                $stmt->bindValue(':is_breaking', $is_breaking, PDO::PARAM_INT);
            }

            if (in_array('published_at', $columns, true)) {
                $stmt->bindValue(':published_at', $publishedAtForDb, $publishedAtForDb === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            }

            if (in_array('publish_at', $columns, true)) {
                $stmt->bindValue(':publish_at', $publish_at_db, $publish_at_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            }

            if (in_array('unpublish_at', $columns, true)) {
                $stmt->bindValue(':unpublish_at', $unpublish_at_db, $unpublish_at_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            }

            if (in_array('seo_title', $columns, true)) {
                $stmt->bindValue(':seo_title', $seo_title, PDO::PARAM_STR);
            }

            if (in_array('seo_description', $columns, true)) {
                $stmt->bindValue(':seo_description', $seo_description, PDO::PARAM_STR);
            }

            if (in_array('seo_keywords', $columns, true)) {
                $stmt->bindValue(':seo_keywords', $seo_keywords, PDO::PARAM_STR);
            }

            foreach ($imageCols as $ic) {
                $stmt->bindValue(':' . $ic, $imagePath, $imagePath ? PDO::PARAM_STR : PDO::PARAM_NULL);
            }

            $stmt->execute();
            $newsId = (int)$pdo->lastInsertId();

            try {
                if (function_exists('gdy_db_table_exists') && gdy_db_table_exists($pdo, 'i18n_fields')) {
                    $trPost = isset($_POST['tr']) && is_array($_POST['tr']) ? $_POST['tr'] : [];
                    $up = $pdo->prepare("INSERT INTO i18n_fields (scope,item_id,lang,field,value) VALUES ('news',?,?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value), updated_at=CURRENT_TIMESTAMP");
                    $del = $pdo->prepare("DELETE FROM i18n_fields WHERE scope='news' AND item_id=? AND lang=? AND field=?");

                    foreach (['en','fr'] as $L) {
                        $row = isset($trPost[$L]) && is_array($trPost[$L]) ? $trPost[$L] : [];
                        foreach (['title','excerpt','content'] as $F) {
                            $val = isset($row[$F]) ? trim((string)$row[$F]) : '';
                            if ($val === '') {
                                $del->execute([$newsId, $L, $F]);
                            } else {
                                $up->execute([$newsId, $L, $F, $val]);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                error_log('[i18n] save translations failed (create): ' . $e->getMessage());
            }

            try {
                if ($status === 'published') {
                    $u = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
                    $newsUrl = $u !== '' ? ($u . '/news/id/' . $newsId) : '';
                    $sitemapUrl = $u !== '' ? ($u . '/sitemap.xml') : '';
                    if ($pdo instanceof PDO && function_exists('gdy_indexnow_submit_safe')) {
                        gdy_indexnow_submit_safe($pdo, array_filter([$newsUrl, $sitemapUrl]));
                    }
                }
            } catch (\Throwable $e) {
                error_log('[IndexNow] create ping failed: ' . $e->getMessage());
            }

            try {
                if (function_exists('gdy_sync_news_tags')) {
                    gdy_sync_news_tags($pdo, $newsId, (string)($tags_input ?? ''));
                }
            } catch (\Throwable $e) {
                error_log('Error syncing tags: ' . $e->getMessage());
            }

            try {
                if (isset($_FILES['attachments']) && function_exists('gdy_save_news_attachments')) {
                    $tmpErrors = [];
                    gdy_save_news_attachments($pdo, $newsId, (array)$_FILES['attachments'], $tmpErrors);
                }
            } catch (\Throwable $e) {
                error_log('Error saving attachments: ' . $e->getMessage());
            }

            $root = dirname(__DIR__, 2);
            if (function_exists('gdy_unlink')) {
                gdy_unlink($root . '/cache/sitemap.xml');
                gdy_unlink($root . '/cache/rss.xml');
            }

            header('Location: edit.php?id=' . $newsId . '&created=1');
            exit;
        } catch (\Throwable $e) {
            error_log('Error inserting news: ' . $e->getMessage());
            $errors['general'] = __('t_c38467d8bb', 'حدث خطأ أثناء حفظ الخبر. يرجى المحاولة مرة أخرى.');
        }
    }
}

$__base = defined('GODYAR_BASE_URL') ? rtrim((string)GODYAR_BASE_URL, '/') : '';
$pageHead =
    '<link rel="stylesheet" href="' . $__base . '/assets/admin/editor/gdy-editor.css?v=' . (string)@filemtime(__DIR__ . '/../../assets/admin/editor/gdy-editor.css') . '">' .
    '<link rel="stylesheet" href="' . $__base . '/admin/assets/css/admin-news-editor.css?v=' . (string)@filemtime(__DIR__ . '/../assets/css/admin-news-editor.css') . '">';

$pageScripts =
    '<script src="' . $__base . '/assets/admin/editor/gdy-editor.js?v=' . (string)@filemtime(__DIR__ . '/../../assets/admin/editor/gdy-editor.js') . '"></script>' .
    '<script src="' . $__base . '/assets/admin/editor/editor-init.js?v=' . (string)@filemtime(__DIR__ . '/../../assets/admin/editor/editor-init.js') . '"></script>';

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../layout/sidebar.php';
?>

<style nonce="<?= h($cspNonce ?? '') ?>">
html, body { overflow-x: hidden; }
@media (min-width: 992px) { .admin-content { margin-right: 260px !important; } }
.admin-content .gdy-page .container-fluid { max-width: 1200px; margin: 0 auto; padding: 1.5rem 1rem 2rem; }
.gdy-header{
  display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;
  padding:1.25rem 1.5rem;margin-bottom:1rem;border-radius:1rem;
  background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff;
}
.gdy-header h1{margin:0 0 .3rem;font-size:1.4rem;font-weight:700}
.gdy-header p{margin:0;font-size:.9rem;opacity:.9}
.gdy-actions{display:flex;flex-wrap:wrap;gap:.5rem}
.gdy-card{
  background:rgba(15,23,42,.96);border-radius:1rem;border:1px solid rgba(148,163,184,.25);
  box-shadow:0 15px 45px rgba(15,23,42,.18);overflow:hidden;color:#e5e7eb;
}
.gdy-card-header{
  padding:1rem 1.25rem;border-bottom:1px solid rgba(148,163,184,.18);
  display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;
}
.gdy-card-body{padding:1.25rem}
.gdy-form-label{font-weight:600;font-size:.85rem;margin-bottom:.25rem;display:block}
.form-control,.form-select{
  border-radius:.7rem;border-color:rgba(148,163,184,.35);background-color:rgba(15,23,42,.85);color:#f8fafc;
}
.form-control:focus,.form-select:focus{
  border-color:#38bdf8;box-shadow:0 0 0 .2rem rgba(56,189,248,.15);
  background-color:rgba(15,23,42,.92);color:#fff;
}
.form-text{color:#cbd5e1}
.invalid-feedback{display:block;font-size:.8rem}
.gdy-options-box{
  border-radius:.9rem;border:1px solid rgba(148,163,184,.25);
  background:radial-gradient(circle at top left, rgba(15,23,42,.95), rgba(15,23,42,1));
  padding:1rem;color:#e5e7eb;
}
.gdy-options-box h3{font-size:.95rem;margin-bottom:.75rem}
.gdy-btn{
  border-radius:.7rem;border:none;padding:.55rem 1.1rem;font-size:.85rem;font-weight:600;
  display:inline-flex;align-items:center;gap:.5rem;text-decoration:none;cursor:pointer;transition:all .2s ease;
}
.gdy-btn-primary{background:linear-gradient(135deg,#0ea5e9,#2563eb);color:#fff}
.gdy-btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
.gdy-btn-secondary{background:rgba(15,23,42,1);color:#fff;border:1px solid rgba(148,163,184,.22)}
.gdy-btn-secondary:hover{background:rgba(15,23,42,.9);transform:translateY(-1px)}
.gdy-dropzone{
  border-radius:.9rem;border:1.5px dashed rgba(148,163,184,.55);background:rgba(15,23,42,.75);
  padding:1rem;text-align:center;cursor:pointer;transition:all .2s ease;
}
.gdy-dropzone:hover,.gdy-dropzone.is-dragover{border-color:#38bdf8;background:rgba(15,23,42,.95)}
.gdy-dropzone-inner i{font-size:1.6rem;color:#38bdf8}
.gdy-image-preview img{max-height:220px;object-fit:contain}
.tags-container{
  display:flex;flex-wrap:wrap;gap:.35rem;min-height:42px;padding:.45rem .55rem;border-radius:.6rem;
  background:rgba(15,23,42,.8);border:1px dashed rgba(148,163,184,.45);
}
.tag-pill{
  display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .55rem;border-radius:999px;
  background:rgba(37,99,235,.18);color:#dbeafe
}
.tag-pill button{border:none;background:transparent;color:#fff;cursor:pointer}
.gdy-inline-pills{display:flex;gap:.5rem;flex-wrap:wrap}
.gdy-inline-pill{
  padding:.28rem .6rem;border-radius:999px;background:rgba(148,163,184,.12);
  border:1px solid rgba(148,163,184,.15);font-size:.78rem;color:#cbd5e1
}
.gdy-checklist{display:grid;gap:.45rem}
.gdy-checklist li{display:flex;align-items:center;gap:.5rem;font-size:.92rem;color:rgba(226,232,240,.92)}
.gdy-serp-preview{background:#fff;color:#111827;border-radius:14px;padding:14px}
.gdy-serp-title{color:#1d4ed8;font-size:1.05rem;line-height:1.35;font-weight:700}
.gdy-serp-url{font-size:.8rem;color:#166534;margin:.25rem 0}
.gdy-serp-desc{font-size:.88rem;color:#374151;line-height:1.5}
.gdy-og-preview{border:1px solid rgba(148,163,184,.25);border-radius:14px;overflow:hidden;background:rgba(2,6,23,.35)}
.gdy-og-img{height:150px;background:rgba(15,23,42,.6);display:flex;align-items:center;justify-content:center;color:rgba(226,232,240,.7);font-size:.9rem}
.gdy-og-img img{width:100%;height:100%;object-fit:cover;display:block}
.gdy-og-meta{padding:.75rem .9rem}
.gdy-og-domain{font-size:.75rem;color:rgba(148,163,184,.95);margin-bottom:.25rem;direction:ltr;text-align:left}
.gdy-og-title{font-weight:800;font-size:.98rem;line-height:1.25;margin-bottom:.25rem;color:rgba(226,232,240,.98)}
.gdy-og-desc{font-size:.85rem;line-height:1.35;color:rgba(203,213,225,.92);max-height:3.7em;overflow:hidden}
.gdy-internal-links{
  border:1px dashed rgba(148,163,184,.25);border-radius:14px;padding:.75rem .85rem;background:rgba(2,6,23,.25)
}
.gdy-internal-links .results a{
  display:block;padding:.45rem .55rem;border-radius:10px;border:1px solid rgba(148,163,184,.18);
  margin-top:.4rem;color:rgba(226,232,240,.95);text-decoration:none
}
.gdy-internal-links .results a:hover{background:rgba(148,163,184,.12)}
@media (max-width: 767.98px){
  .gdy-header{padding:1.25rem;margin:0 -.75rem 1rem}
  .gdy-card-body{padding:1rem}
}
</style>

<div class="admin-content gdy-page">
  <div class="container-fluid">

    <div class="gdy-header">
      <div>
        <h1><?php echo h(__('t_0d1f6ecf66', 'إضافة خبر جديد')); ?></h1>
        <p><?php echo h(__('t_459cc72253', 'املأ النموذج أدناه لإضافة خبر جديد إلى الموقع.')); ?></p>
      </div>
      <div class="gdy-actions">
        <a href="index.php" class="gdy-btn gdy-btn-secondary"><?php echo h(__('t_f2401e0914', 'قائمة الأخبار')); ?></a>
      </div>
    </div>

    <?php if (!empty($errors['general'])): ?>
      <div class="alert alert-danger"><?php echo h($errors['general']); ?></div>
    <?php endif; ?>

    <div class="gdy-card mb-4">
      <div class="gdy-card-header">
        <h2><?php echo h(__('t_ab484184b3', 'بيانات الخبر')); ?></h2>
        <small><?php echo h(__('t_fa60f5381a', 'املأ الحقول الأساسية، ويمكن تعديل باقي الإعدادات لاحقًا.')); ?></small>
      </div>

      <div class="gdy-card-body">
        <form method="post"
              action=""
              enctype="multipart/form-data"
              id="news-editor-form"
              data-autosave-url="autosave.php"
              data-news-id="0"
              data-compare-url="ajax_revision_compare.php">

          <?php if (function_exists('csrf_field')) { csrf_field(); } ?>

          <div class="row g-3">
            <div class="col-md-8">

              <div class="mb-3">
                <label for="title" class="gdy-form-label"><span class="text-danger">*</span> <?php echo h(__('t_6dc6588082', 'العنوان')); ?></label>
                <input id="title"
                       type="text"
                       name="title"
                       class="form-control form-control-sm <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>"
                       autocomplete="off"
                       value="<?php echo h($title); ?>"
                       placeholder="<?php echo h(__('t_64a18e598a', 'اكتب عنوان الخبر هنا')); ?>">
                <?php if (isset($errors['title'])): ?>
                  <div class="invalid-feedback"><?php echo h($errors['title']); ?></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mt-1">
                  <small class="text-muted"><?php echo h(__('t_9c84024cc6', 'اجعل العنوان واضحاً وجاذباً.')); ?></small>
                  <small id="title-counter">0 / 120</small>
                </div>
                <div class="gdy-inline-pills mt-2">
                  <span class="gdy-inline-pill" id="gdyHeadlineScore">تحليل العنوان: —</span>
                  <span class="gdy-inline-pill" id="gdyAutosaveStatus">الحفظ التلقائي: غير نشط</span>
                  <span class="gdy-inline-pill" id="gdyScheduleStatus">الجدولة: غير محددة</span>
                </div>
              </div>

              <div class="mb-3">
                <label for="slug" class="gdy-form-label"><?php echo h(__('t_0781965540', 'الرابط (Slug)')); ?></label>
                <input type="text"
                       id="slug"
                       autocomplete="off"
                       name="slug"
                       class="form-control form-control-sm <?php echo isset($errors['slug']) ? 'is-invalid' : ''; ?>"
                       value="<?php echo h($slug); ?>"
                       placeholder="<?php echo h(__('t_049c6abb70', 'اتركه فارغاً لتوليد رابط تلقائي من العنوان')); ?>">
                <?php if (isset($errors['slug'])): ?>
                  <div class="invalid-feedback"><?php echo h($errors['slug']); ?></div>
                <?php else: ?>
                  <div class="form-text"><?php echo h(__('t_6881e8f9d5', 'مثال:')); ?> <code>breaking-news-2025</code> <?php echo h(__('t_be9a7dfdd3', '— يمكنك تغييره يدويًا.')); ?></div>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <label for="excerpt" class="gdy-form-label"><?php echo h(__('t_excerpt', 'المقتطف / الملخص')); ?></label>
                <textarea id="excerpt" name="excerpt" rows="3" class="form-control form-control-sm" placeholder="ملخص قصير للخبر يظهر في البطاقات ونتائج البحث"><?php echo h($excerpt); ?></textarea>
              </div>

              <div class="mb-3">
                <label for="content" class="gdy-form-label"><?php echo h(__('t_b649baf3ad', 'نص الخبر / المحتوى')); ?></label>

                <div class="d-flex flex-wrap gap-2 align-items-center mt-2 mb-2">
                  <label class="form-check form-check-inline small m-0">
                    <input id="strip_formatting" class="form-check-input" type="checkbox" name="strip_formatting" value="1" checked>
                    <span class="form-check-label"><?php echo h(__('t_strip_fmt', 'إزالة التنسيق')); ?></span>
                  </label>
                  <label class="form-check form-check-inline small m-0">
                    <input id="strip_bg" class="form-check-input" type="checkbox" name="strip_bg" value="1" checked>
                    <span class="form-check-label"><?php echo h(__('t_strip_bg', 'إزالة لون الخلفية')); ?></span>
                  </label>
                  <button type="button" class="btn btn-sm btn-outline-light" id="btn-clean-content"><?php echo h(__('t_clean_now', 'تنظيف المحتوى الآن')); ?></button>
                  <span class="text-muted small"><?php echo h(__('t_clean_hint', 'يتم تطبيق التنظيف قبل الحفظ إذا كانت الخيارات مفعّلة.')); ?></span>
                </div>

                <textarea id="content"
                          data-gdy-editor="1"
                          name="content"
                          rows="12"
                          class="form-control form-control-sm"
                          placeholder="<?php echo h(__('t_52461cefbe', 'اكتب نص الخبر كاملاً أو الصقه هنا.')); ?>"><?php echo h($content); ?></textarea>

                <div class="form-text mt-2">يدعم اللصق من Word/Office والتطبيقات المشابهة مع تنظيف التنسيق غير المرغوب.</div>

                <div class="gdy-options-box mt-3" id="i18n-inline-box">
                  <h3 class="mb-2">ترجمات المقال (EN/FR)</h3>
                  <div class="d-flex gap-2 flex-wrap mb-2">
                    <button type="button" class="btn btn-sm btn-outline-light gdy-tr-tab active" data-lang="en">English</button>
                    <button type="button" class="btn btn-sm btn-outline-light gdy-tr-tab" data-lang="fr">Français</button>
                    <span class="text-muted small" style="align-self:center;">اختيارية — تُحفظ في قاعدة البيانات</span>
                  </div>

                  <div class="gdy-tr-pane" data-lang="en">
                    <div class="mb-2">
                      <label class="gdy-form-label" for="tr_en_title">Title (EN)</label>
                      <input id="tr_en_title" type="text" class="form-control form-control-sm" name="tr[en][title]" value="<?php echo h((string)($_POST['tr']['en']['title'] ?? '')); ?>" autocomplete="off">
                    </div>
                    <div class="mb-2">
                      <label class="gdy-form-label" for="tr_en_excerpt">Excerpt (EN)</label>
                      <textarea id="tr_en_excerpt" class="form-control form-control-sm" name="tr[en][excerpt]" rows="2"><?php echo h((string)($_POST['tr']['en']['excerpt'] ?? '')); ?></textarea>
                    </div>
                    <div class="mb-1">
                      <label class="gdy-form-label" for="tr_en_content">Content (EN)</label>
                      <textarea id="tr_en_content" class="form-control form-control-sm" name="tr[en][content]" rows="6"><?php echo h((string)($_POST['tr']['en']['content'] ?? '')); ?></textarea>
                    </div>
                  </div>

                  <div class="gdy-tr-pane d-none" data-lang="fr">
                    <div class="mb-2">
                      <label class="gdy-form-label" for="tr_fr_title">Titre (FR)</label>
                      <input id="tr_fr_title" type="text" class="form-control form-control-sm" name="tr[fr][title]" value="<?php echo h((string)($_POST['tr']['fr']['title'] ?? '')); ?>" autocomplete="off">
                    </div>
                    <div class="mb-2">
                      <label class="gdy-form-label" for="tr_fr_excerpt">Résumé (FR)</label>
                      <textarea id="tr_fr_excerpt" class="form-control form-control-sm" name="tr[fr][excerpt]" rows="2"><?php echo h((string)($_POST['tr']['fr']['excerpt'] ?? '')); ?></textarea>
                    </div>
                    <div class="mb-1">
                      <label class="gdy-form-label" for="tr_fr_content">Contenu (FR)</label>
                      <textarea id="tr_fr_content" class="form-control form-control-sm" name="tr[fr][content]" rows="6"><?php echo h((string)($_POST['tr']['fr']['content'] ?? '')); ?></textarea>
                    </div>
                  </div>
                </div>

                <div class="gdy-internal-links mt-3" id="internal-links-panel">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-info" id="btn-suggest-links"><?php echo h(__('t_il_suggest','اقتراح روابط داخلية')); ?></button>
                    <input type="text" class="form-control form-control-sm" id="internal-link-query" style="max-width:260px" placeholder="<?php echo h(__('t_il_query','كلمة/عبارة (اختياري)')); ?>" autocomplete="off">
                    <span class="text-muted small"><?php echo h(__('t_il_tip','حدد نصًا داخل المحتوى ثم اضغط اقتراح.')); ?></span>
                  </div>
                  <div class="results mt-2" id="internal-links-results"></div>
                </div>
              </div>

              <div class="mb-3">
                <label class="gdy-form-label" for="image-input"><?php echo h(__('t_cd376ca9a8', 'صورة الخبر (اختياري)')); ?></label>
                <div id="image-dropzone" class="gdy-dropzone">
                  <input type="file" name="image" id="image-input" accept="image/*" class="d-none">
                  <div class="gdy-dropzone-inner">
                    <p class="mb-1"><?php echo h(__('t_82302b8afb', 'اسحب وأفلت الصورة هنا أو اضغط للاختيار')); ?></p>
                    <small class="text-muted"><?php echo h(__('t_5968ccc71d', 'يفضل صور JPG أو PNG أو GIF أو WebP بحجم أقل من 5MB.')); ?></small>
                  </div>
                  <div class="mt-2 d-flex gap-2 flex-wrap">
                    <button type="button" id="gdy-pick-featured" class="btn btn-sm btn-outline-light">اختيار من المكتبة</button>
                    <small class="text-muted">يمكنك اختيار صورة الخبر من مكتبة الوسائط بدون رفع جديد.</small>
                  </div>
                  <input id="image_url" type="hidden" name="image_url" value="<?php echo h($image_url); ?>">
                </div>

                <div id="image-preview" class="gdy-image-preview <?php echo $imagePath ? '' : 'd-none'; ?> mt-2">
                  <img src="<?php echo $imagePath ? h((strpos($imagePath, '/') === 0 ? '' : '/') . $imagePath) : ''; ?>" alt="معاينة الصورة" class="img-fluid rounded mb-2">
                  <div class="small text-muted" id="image-info"><?php echo $imagePath ? h(basename($imagePath)) : ''; ?></div>
                </div>

                <?php if (isset($errors['image'])): ?>
                  <div class="invalid-feedback d-block"><?php echo h($errors['image']); ?></div>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <label class="gdy-form-label" for="attachments-input"><?php echo h(__('t_5deb5044ef', 'مرفقات الخبر (PDF / Word / Excel ...)')); ?></label>
                <input type="file" name="attachments[]" id="attachments-input" class="form-control form-control-sm" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.txt,.rtf,image/*">
                <div class="form-text"><?php echo h(__('t_02b2d51ef0', 'يمكنك رفع عدة ملفات (حتى 20MB لكل ملف).')); ?></div>
                <div id="attachments-preview" class="mt-2 small text-muted"></div>
              </div>

            </div>

            <div class="col-md-4">

              <div class="gdy-options-box mb-3">
                <h3><?php echo h(__('t_cf14329701', 'التصنيف')); ?></h3>

                <div class="mb-3">
                  <label for="category_id" class="gdy-form-label"><span class="text-danger">*</span> <?php echo h(__('t_cf14329701', 'التصنيف')); ?></label>
                  <select id="category_id" name="category_id" class="form-select form-select-sm <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>">
                    <option value="0"><?php echo h(__('t_0a8d417cf7', '-- اختر التصنيف --')); ?></option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?php echo (int)$cat['id']; ?>" <?php echo (int)$cat['id'] === (int)$category_id ? 'selected' : ''; ?>>
                        <?php echo h($cat['name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?php if (isset($errors['category_id'])): ?>
                    <div class="invalid-feedback"><?php echo h($errors['category_id']); ?></div>
                  <?php endif; ?>
                </div>

                <?php if (!empty($opinionAuthors)): ?>
                  <div class="mb-3">
                    <label for="opinion_author_id" class="gdy-form-label"><?php echo h(__('t_a53485b1ba', 'كاتب رأي (إن كان مقال رأي)')); ?></label>
                    <select id="opinion_author_id" name="opinion_author_id" class="form-select form-select-sm">
                      <option value="0"><?php echo h(__('t_7f3152db47', '-- ليس مقال رأي --')); ?></option>
                      <?php foreach ($opinionAuthors as $oa): ?>
                        <option value="<?php echo (int)$oa['id']; ?>" <?php echo (int)$oa['id'] === (int)$opinion_author_id ? 'selected' : ''; ?>>
                          <?php echo h($oa['name']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                <?php endif; ?>
              </div>

              <div class="gdy-options-box mb-3">
                <h3><?php echo h(__('t_917daa2b85', 'النشر والحالة')); ?></h3>

                <div class="mb-2">
                  <label for="status" class="gdy-form-label"><?php echo h(__('t_1253eb5642', 'الحالة')); ?></label>
                  <select id="status" name="status" class="form-select form-select-sm">
                    <?php if ($isWriter): ?>
                      <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>><?php echo h(__('t_9071af8f2d', 'مسودة')); ?></option>
                      <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>><?php echo h(__('t_e9210fb9c2', 'بانتظار المراجعة')); ?></option>
                    <?php else: ?>
                      <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>><?php echo h(__('t_9071af8f2d', 'مسودة')); ?></option>
                      <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>><?php echo h(__('t_e9210fb9c2', 'بانتظار المراجعة')); ?></option>
                      <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>><?php echo h(__('t_aeb4d514db', 'جاهز للنشر')); ?></option>
                      <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>><?php echo h(__('t_ecfb62b400', 'منشور')); ?></option>
                      <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>><?php echo h(__('t_2e67aea8ca', 'مؤرشف')); ?></option>
                    <?php endif; ?>
                  </select>
                </div>

                <div class="mb-2">
                  <label for="published_at" class="gdy-form-label"><?php echo h(__('t_9928bed160', 'تاريخ النشر (اختياري)')); ?></label>
                  <input id="published_at" type="datetime-local" name="published_at" class="form-control form-control-sm" value="<?php echo h($published_at); ?>">
                </div>

                <div class="mb-2">
                  <label for="publish_at" class="gdy-form-label"><?php echo h(__('t_9260bd801b', 'جدولة نشر (publish_at)')); ?></label>
                  <input id="publish_at" type="datetime-local" name="publish_at" class="form-control form-control-sm" value="<?php echo h($publish_at); ?>">
                </div>

                <div class="mb-2">
                  <label for="unpublish_at" class="gdy-form-label"><?php echo h(__('t_58e272983c', 'جدولة إلغاء نشر (unpublish_at)')); ?></label>
                  <input id="unpublish_at" type="datetime-local" name="unpublish_at" class="form-control form-control-sm" value="<?php echo h($unpublish_at); ?>">
                </div>

                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="featured" id="featured" value="1" <?php echo $featured ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="featured"><?php echo h(__('t_6b9fb336b6', 'خبر مميز (Featured)')); ?></label>
                </div>

                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="is_breaking" id="is_breaking" value="1" <?php echo $is_breaking ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="is_breaking"><?php echo h(__('t_d8a9550063', 'خبر عاجل (Breaking)')); ?></label>
                </div>
              </div>

              <div class="gdy-options-box mb-3">
                <h3><?php echo h(__('t_5584163b0c', 'إعدادات SEO')); ?></h3>

                <div class="mb-2">
                  <label class="gdy-form-label" for="seo_title">SEO Title</label>
                  <input type="text" id="seo_title" autocomplete="off" name="seo_title" class="form-control form-control-sm" value="<?php echo h($seo_title); ?>" placeholder="عنوان لمحركات البحث (اختياري)">
                </div>

                <div class="mb-2">
                  <label class="gdy-form-label" for="seo_description">SEO Description</label>
                  <textarea id="seo_description" name="seo_description" rows="3" class="form-control form-control-sm" placeholder="وصف مختصر لمحركات البحث (اختياري)"><?php echo h($seo_description); ?></textarea>
                </div>

                <div class="mb-2">
                  <label class="gdy-form-label" for="seo_keywords">SEO Keywords</label>
                  <input type="text" id="seo_keywords" autocomplete="off" name="seo_keywords" class="form-control form-control-sm" value="<?php echo h($seo_keywords); ?>" placeholder="كلمات مفتاحية (اختياري)">
                </div>
              </div>

              <div class="gdy-options-box mb-3">
                <h3><?php echo h(__('t_84c1b773c5', 'الوسوم')); ?></h3>
                <div class="mb-2">
                  <label class="gdy-form-label" for="tag-input"><?php echo h(__('t_51071ad5c6', 'وسوم (Tags)')); ?></label>
                  <div class="tags-input-wrapper">
                    <div id="tags-container" class="tags-container" data-initial-tags="<?php echo h($tags); ?>"></div>
                    <div class="input-group input-group-sm mt-2">
                      <input type="text" id="tag-input" class="form-control form-control-sm" placeholder="اكتب الوسم ثم اضغط إضافة أو Enter" autocomplete="off">
                      <button type="button" class="btn btn-outline-secondary" id="add-tag-btn"><?php echo h(__('t_7764b0999a', 'إضافة وسم')); ?></button>
                    </div>
                    <button type="button" class="btn btn-link btn-sm p-0 mt-1" id="auto-tags-btn"><?php echo h(__('t_0c8a1e0b20', 'اقتراح وسوم تلقائيًا من العنوان')); ?></button>
                    <input type="hidden" name="tags" id="tags-hidden" value="<?php echo h($tags); ?>">
                  </div>
                </div>
              </div>

            </div>
          </div>

          <div class="gdy-card mt-3" id="prepublish-checklist-card">
            <div class="gdy-card-header">
              <h3><?php echo h(__('t_precheck', 'قائمة التحقق قبل النشر')); ?></h3>
              <span class="badge bg-secondary-subtle text-secondary-emphasis" id="precheck-badge">—</span>
            </div>
            <div class="gdy-card-body">
              <ul class="list-unstyled mb-0 gdy-checklist" id="prepublish-checklist">
                <li data-key="title"><span class="ci">○</span><span>العنوان</span></li>
                <li data-key="category"><span class="ci">○</span><span>التصنيف</span></li>
                <li data-key="content"><span class="ci">○</span><span>المحتوى</span></li>
                <li data-key="tags"><span class="ci">○</span><span>الوسوم</span></li>
                <li data-key="seo"><span class="ci">○</span><span>SEO</span></li>
                <li data-key="image"><span class="ci">○</span><span>صورة الخبر</span></li>
              </ul>
            </div>
          </div>

          <div class="gdy-card mt-3" id="gdy-serp-preview-card">
            <div class="gdy-card-header">
              <h3>معاينة نتائج البحث</h3>
            </div>
            <div class="gdy-card-body">
              <div class="gdy-serp-preview">
                <div class="gdy-serp-title" id="serp-title">—</div>
                <div class="gdy-serp-url" id="serp-url">—</div>
                <div class="gdy-serp-desc" id="serp-desc">—</div>
              </div>
              <div class="d-flex gap-3 small text-muted mt-2">
                <span id="serp-title-count">0 / 60</span>
                <span id="serp-desc-count">0 / 160</span>
              </div>
            </div>
          </div>

          <div class="gdy-card mt-3" id="og-preview-card">
            <div class="gdy-card-header">
              <h3>معاينة المشاركة (OpenGraph)</h3>
            </div>
            <div class="gdy-card-body">
              <div class="gdy-og-preview">
                <div class="gdy-og-img" id="ogp-img"><span class="muted">صورة المعاينة</span></div>
                <div class="gdy-og-meta">
                  <div class="gdy-og-domain" id="ogp-domain">—</div>
                  <div class="gdy-og-title" id="ogp-title">—</div>
                  <div class="gdy-og-desc" id="ogp-desc">—</div>
                </div>
              </div>
            </div>
          </div>

          <div class="gdy-card-header mt-3">
            <div class="small text-muted">تأكد من مراجعة البيانات قبل الحفظ.</div>
            <div class="d-flex gap-2">
              <button type="submit" class="gdy-btn gdy-btn-primary">حفظ الخبر</button>
              <a href="index.php" class="gdy-btn gdy-btn-secondary">إلغاء والعودة</a>
            </div>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>

<div id="gdy-media-backdrop" class="gdy-modal-backdrop"></div>
<div id="gdy-media-modal" class="gdy-modal" aria-hidden="true">
  <div class="gdy-modal-header">
    <div class="gdy-modal-title">مكتبة الوسائط</div>
    <div style="display:flex; gap:8px;">
      <a href="../media/upload.php" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info">رفع جديد</a>
      <button type="button" id="gdy-media-close">إغلاق</button>
    </div>
  </div>
  <iframe id="gdy-media-frame" src="../media/picker.php?target=content" style="width:100%;height:calc(90vh - 54px);border:0;"></iframe>
</div>

<script nonce="<?= h($cspNonce ?? '') ?>">
document.addEventListener('DOMContentLoaded', function () {
  var titleInput = document.getElementById('title');
  var titleCounter = document.getElementById('title-counter');
  var slugInput = document.getElementById('slug');
  var userEditedSlug = false;

  function updateTitleCounter() {
    if (!titleInput || !titleCounter) return;
    var len = titleInput.value.length;
    titleCounter.textContent = len + ' / 120';
  }

  function slugify(str) {
    try {
      return String(str).trim()
        .replace(/[^\p{L}\p{N}]+/gu, '-')
        .replace(/^-+|-+$/g, '');
    } catch (e) {
      return String(str).trim()
        .replace(/[^A-Za-z0-9\u0600-\u06FF]+/g, '-')
        .replace(/^-+|-+$/g, '');
    }
  }

  if (titleInput) {
    titleInput.addEventListener('input', updateTitleCounter);
    updateTitleCounter();
  }

  if (slugInput) {
    slugInput.addEventListener('input', function () {
      userEditedSlug = slugInput.value.trim() !== '';
    });
  }

  if (titleInput && slugInput) {
    titleInput.addEventListener('input', function () {
      if (!userEditedSlug) {
        slugInput.value = slugify(titleInput.value);
      }
    });
  }

  var dropzone = document.getElementById('image-dropzone');
  var fileInput = document.getElementById('image-input');
  var previewWrap = document.getElementById('image-preview');
  var previewImg = previewWrap ? previewWrap.querySelector('img') : null;
  var infoEl = document.getElementById('image-info');

  function handleFile(file) {
    if (!file || !previewWrap || !previewImg) return;
    if (!file.type || file.type.indexOf('image/') !== 0) {
      alert('الرجاء اختيار ملف صورة صالح.');
      return;
    }
    previewWrap.classList.remove('d-none');
    previewImg.src = URL.createObjectURL(file);
    if (infoEl) {
      var sizeKB = Math.round(file.size / 1024);
      infoEl.textContent = file.name + ' (' + sizeKB + ' KB)';
    }
  }

  if (dropzone && fileInput) {
    dropzone.addEventListener('click', function (e) {
      if (e.target && e.target.id === 'gdy-pick-featured') return;
      fileInput.click();
    });

    fileInput.addEventListener('change', function (e) {
      var file = e.target.files && e.target.files[0];
      if (file) handleFile(file);
    });

    ['dragenter', 'dragover'].forEach(function (evtName) {
      dropzone.addEventListener(evtName, function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add('is-dragover');
      });
    });

    ['dragleave', 'dragend', 'drop'].forEach(function (evtName) {
      dropzone.addEventListener(evtName, function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('is-dragover');
      });
    });

    dropzone.addEventListener('drop', function (e) {
      var dt = e.dataTransfer;
      if (!dt || !dt.files || !dt.files.length) return;
      fileInput.files = dt.files;
      handleFile(dt.files[0]);
    });
  }

  var tagsContainer = document.getElementById('tags-container');
  var tagInput = document.getElementById('tag-input');
  var addTagBtn = document.getElementById('add-tag-btn');
  var autoTagsBtn = document.getElementById('auto-tags-btn');
  var tagsHidden = document.getElementById('tags-hidden');
  var tagsList = [];

  function renderTags() {
    if (!tagsContainer || !tagsHidden) return;
    tagsContainer.innerHTML = '';
    tagsList.forEach(function (tag, index) {
      var pill = document.createElement('span');
      pill.className = 'tag-pill';
      pill.appendChild(document.createTextNode(tag));

      var removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.innerHTML = '&times;';
      removeBtn.addEventListener('click', function () {
        tagsList.splice(index, 1);
        renderTags();
      });

      pill.appendChild(removeBtn);
      tagsContainer.appendChild(pill);
    });
    tagsHidden.value = tagsList.join(', ');
  }

  function addTagFromInput() {
    if (!tagInput) return;
    var val = tagInput.value.trim();
    if (!val) return;
    if (tagsList.indexOf(val) === -1) {
      tagsList.push(val);
      renderTags();
    }
    tagInput.value = '';
    tagInput.focus();
  }

  if (tagsContainer && tagsHidden) {
    var initial = tagsContainer.getAttribute('data-initial-tags') || '';
    if (initial) {
      initial.split(/[،,]/).forEach(function (t) {
        var v = t.trim();
        if (v && tagsList.indexOf(v) === -1) tagsList.push(v);
      });
      renderTags();
    }
  }

  if (addTagBtn) addTagBtn.addEventListener('click', addTagFromInput);
  if (tagInput) {
    tagInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        addTagFromInput();
      }
    });
  }

  if (autoTagsBtn && titleInput) {
    autoTagsBtn.addEventListener('click', function () {
      var source = titleInput.value || '';
      var words = source
        .replace(/[^\u0600-\u06FFa-zA-Z0-9\s]/g, ' ')
        .split(/\s+/)
        .map(function (w) { return w.trim(); })
        .filter(function (w) { return w.length > 3; });

      var unique = [];
      words.forEach(function (w) {
        if (unique.indexOf(w) === -1) unique.push(w);
      });

      unique.slice(0, 6).forEach(function (w) {
        if (tagsList.indexOf(w) === -1) tagsList.push(w);
      });

      renderTags();
    });
  }

  var attachmentsInput = document.getElementById('attachments-input');
  var attachmentsPreview = document.getElementById('attachments-preview');
  if (attachmentsInput && attachmentsPreview) {
    attachmentsInput.addEventListener('change', function () {
      var files = Array.from(attachmentsInput.files || []);
      if (!files.length) {
        attachmentsPreview.textContent = '';
        return;
      }
      attachmentsPreview.innerHTML = files.map(function (f) {
        var kb = Math.round((f.size || 0) / 1024);
        return '<div class="d-flex align-items-center gap-2 mb-1"><span>' + f.name + '</span><span class="text-muted">(' + kb + ' KB)</span></div>';
      }).join('');
    });
  }

  var tabs = document.querySelectorAll('.gdy-tr-tab');
  var panes = document.querySelectorAll('.gdy-tr-pane');
  function showTrPane(lang) {
    panes.forEach(function (p) {
      p.classList.toggle('d-none', p.getAttribute('data-lang') !== lang);
    });
    tabs.forEach(function (b) {
      b.classList.toggle('active', b.getAttribute('data-lang') === lang);
    });
  }
  tabs.forEach(function (b) {
    b.addEventListener('click', function () { showTrPane(b.getAttribute('data-lang')); });
  });
  showTrPane('en');

  var btnClean = document.getElementById('btn-clean-content');
  var contentEl = document.getElementById('content');
  if (btnClean && contentEl) {
    btnClean.addEventListener('click', function () {
      var html = contentEl.value || '';
      html = html.replace(/\sbgcolor\s*=\s*(['"]).*?\1/gi, '');
      html = html.replace(/background-color\s*:\s*[^;"]+;?/gi, '');
      html = html.replace(/\sstyle\s*=\s*(['"]).*?\1/gi, '');
      html = html.replace(/\sclass\s*=\s*(['"]).*?\1/gi, '');
      html = html.replace(/<\/?font[^>]*>/gi, '');
      html = html.replace(/\s{2,}/g, ' ');
      contentEl.value = html.trim();
      contentEl.dispatchEvent(new Event('input', { bubbles: true }));
    });
  }

  function plainText(html) {
    var div = document.createElement('div');
    div.innerHTML = html || '';
    return (div.textContent || div.innerText || '').replace(/\s+/g, ' ').trim();
  }

  function topWords(text, max) {
    text = (text || '').toLowerCase().replace(/[^\u0600-\u06FFa-z0-9\s]/gi, ' ');
    var words = text.split(/\s+/).filter(Boolean);
    var stop = new Set(['في','على','من','إلى','عن','هذا','هذه','ذلك','تلك','و','أو','ثم','كما','مع','ما','لم','لن','قد','هو','هي','هم','هن','the','and','or','to','of','in','on','for','a','an']);
    var freq = new Map();
    words.forEach(function (w) {
      if (w.length < 3 || stop.has(w)) return;
      freq.set(w, (freq.get(w) || 0) + 1);
    });
    return Array.from(freq.entries()).sort(function (a, b) { return b[1] - a[1]; }).slice(0, max).map(function (x) { return x[0]; });
  }

  var seoTitle = document.getElementById('seo_title');
  var seoDesc = document.getElementById('seo_description');
  var seoKeys = document.getElementById('seo_keywords');
  var touched = { seo_title:false, seo_description:false, seo_keywords:false };

  [seoTitle, seoDesc, seoKeys].forEach(function (el, i) {
    if (!el) return;
    var key = i === 0 ? 'seo_title' : (i === 1 ? 'seo_description' : 'seo_keywords');
    el.addEventListener('input', function () { touched[key] = true; });
  });

  function regenerateSeo() {
    var body = plainText(contentEl ? contentEl.value : '');
    if (seoTitle && !touched.seo_title && !seoTitle.value.trim()) {
      seoTitle.value = (titleInput ? titleInput.value : '').slice(0, 60);
    }
    if (seoDesc && !touched.seo_description && !seoDesc.value.trim()) {
      seoDesc.value = (body || (titleInput ? titleInput.value : '')).slice(0, 160);
    }
    if (seoKeys && !touched.seo_keywords && !seoKeys.value.trim()) {
      seoKeys.value = topWords(((titleInput ? titleInput.value : '') + ' ' + body), 10).join(', ');
    }
  }

  if (titleInput) titleInput.addEventListener('input', regenerateSeo);
  if (contentEl) contentEl.addEventListener('input', regenerateSeo);
  regenerateSeo();

  function updateSerpPreview() {
    var titleEl = document.getElementById('serp-title');
    var urlEl = document.getElementById('serp-url');
    var descEl = document.getElementById('serp-desc');
    var titleCount = document.getElementById('serp-title-count');
    var descCount = document.getElementById('serp-desc-count');
    if (!titleEl || !urlEl || !descEl) return;

    var rawTitle = (seoTitle && seoTitle.value.trim()) || (titleInput && titleInput.value.trim()) || 'عنوان الخبر';
    var rawDesc = (seoDesc && seoDesc.value.trim()) || (document.getElementById('excerpt')?.value.trim()) || 'وصف مختصر يظهر في نتائج البحث.';
    var rawSlug = (slugInput && slugInput.value.trim()) || 'news';

    titleEl.textContent = rawTitle.substring(0, 60);
    urlEl.textContent = (window.GODYAR_BASE_URL || location.origin).replace(/\/+$/, '') + '/news/' + rawSlug.replace(/^\/+/, '');
    descEl.textContent = rawDesc.substring(0, 160);
    if (titleCount) titleCount.textContent = rawTitle.length + ' / 60';
    if (descCount) descCount.textContent = rawDesc.length + ' / 160';

    var ogTitle = document.getElementById('ogp-title');
    var ogDesc = document.getElementById('ogp-desc');
    var ogDomain = document.getElementById('ogp-domain');
    var ogImg = document.getElementById('ogp-img');
    if (ogTitle) ogTitle.textContent = rawTitle;
    if (ogDesc) ogDesc.textContent = rawDesc;
    if (ogDomain) ogDomain.textContent = (window.GODYAR_BASE_URL || location.origin).replace(/^https?:\/\//, '');
    if (ogImg && previewImg && previewImg.src) {
      ogImg.innerHTML = '<img src="' + previewImg.src + '" alt="">';
    }
  }

  ['input', 'change'].forEach(function (evt) {
    [titleInput, slugInput, seoTitle, seoDesc, document.getElementById('excerpt')].forEach(function (el) {
      if (el) el.addEventListener(evt, updateSerpPreview);
    });
  });
  updateSerpPreview();

  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
      e.preventDefault();
      document.getElementById('news-editor-form')?.submit();
    }
  });
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>