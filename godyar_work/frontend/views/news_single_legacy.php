<?php
$relatedNews = [];
try {
    if (($pdo ?? null) instanceof PDO) {
        $relatedNews = gdy_phase33_related_news($pdo, (int)($id ?? 0), (int)($article['category_id'] ?? $news['category_id'] ?? 0), 4);
    }
} catch (Throwable $e) {
    $relatedNews = [];
}
?>
<?php
if (!function_exists('gdy_phase33_related_news')) {
    function gdy_phase33_related_news(PDO $pdo, int $currentId, int $categoryId = 0, int $limit = 4): array
    {
        $limit = max(1, min(8, $limit));

        try {
            if ($categoryId > 0) {
                $sql = "SELECT id, slug, title, COALESCE(image, featured_image, image_path, '') AS image
                        FROM news
                        WHERE status='published' AND id <> ? AND category_id = ?
                        ORDER BY id DESC
                        LIMIT {$limit}";
                $st = $pdo->prepare($sql);
                $st->execute([$currentId, $categoryId]);
                $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
                if ($rows) return $rows;
            }

            $sql = "SELECT id, slug, title, COALESCE(image, featured_image, image_path, '') AS image
                    FROM news
                    WHERE status='published' AND id <> ?
                    ORDER BY id DESC
                    LIMIT {$limit}";
            $st = $pdo->prepare($sql);
            $st->execute([$currentId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}
?>
<?php
if (!function_exists('h')) {
  function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
$article = (isset($article) && is_array($article)) ? $article : ((isset($news) && is_array($news)) ? $news : []);
if (!$article) {
  if (!headers_sent()) { http_response_code(404); }
  echo '<section class="container" style="padding:24px"><div style="padding:16px;border-radius:12px;background:#fff">' . h(__('news.not_found', 'عذراً، لم يتم العثور على الخبر.')) . '</div></section>';
  return;
}
$baseUrl = rtrim((string)($baseUrl ?? ''), '/');
$navBaseUrl = rtrim((string)($navBaseUrl ?? $baseUrl), '/');
$lang = (string)($pageLang ?? (function_exists('gdy_current_lang') ? gdy_current_lang() : 'ar'));
$uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
if ($navBaseUrl === $baseUrl && preg_match('~^/(ar|en|fr)(?=/|$)~i', $uri, $m)) { $navBaseUrl = $baseUrl . '/' . strtolower($m[1]); }
$id = (int)($article['id'] ?? 0);
$slug = trim((string)($article['slug'] ?? ''));
$titleBase = (string)($article['title'] ?? __('news.untitled', 'خبر بدون عنوان'));
$title = function_exists('gdy_tr') ? (string)gdy_tr('news', $id, 'title', $titleBase) : $titleBase;
$bodyBase = (string)($article['content'] ?? $article['body'] ?? $article['description'] ?? '');
$body = function_exists('gdy_tr') ? (string)gdy_tr('news', $id, 'content', $bodyBase) : $bodyBase;
$image = trim((string)($article['featured_image'] ?? $article['image'] ?? $article['image_path'] ?? ''));
if ($image !== '' && !preg_match('~^https?://~i', $image)) { $image = $baseUrl . '/' . ltrim($image, '/'); }
$date = (string)($article['publish_at'] ?? $article['published_at'] ?? $article['created_at'] ?? '');
$comments = [];
$userLoggedIn = false;
try { if (session_status() === PHP_SESSION_NONE && !headers_sent()) { @session_start(); } $userLoggedIn = !empty($_SESSION['user']) || !empty($_SESSION['user_id']); } catch (Throwable $e) {}
if (($pdo ?? null) instanceof PDO && $id > 0) {
  try {
    $hasNewsComments = function_exists('gdy_table_exists_safe') ? gdy_table_exists_safe($pdo, 'news_comments') : false;
    if ($hasNewsComments) {
      $st = $pdo->prepare("SELECT id, COALESCE(name,'') AS author_name, body AS content, created_at FROM news_comments WHERE news_id = ? AND status = 'approved' ORDER BY id ASC LIMIT 100");
      $st->execute([$id]);
      $comments = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } elseif (function_exists('gdy_table_exists_safe') ? gdy_table_exists_safe($pdo, 'comments') : false) {
      $st = $pdo->prepare("SELECT id, COALESCE(author_name,'') AS author_name, COALESCE(body, content, '') AS content, created_at FROM comments WHERE news_id = ? AND status IN ('approved','active') ORDER BY id ASC LIMIT 100");
      $st->execute([$id]);
      $comments = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
  } catch (Throwable $e) { $comments = []; }
}
$resolveWebp = static function (string $url) use ($baseUrl): string {
  if ($url === '' || preg_match('~\.webp(?:$|\?)~i', $url)) { return ''; }
  $candidate = preg_replace('~\.(jpe?g|png)(\?.*)?$~i', '.webp$2', $url);
  if (!$candidate || $candidate === $url) { return ''; }
  $path = parse_url($candidate, PHP_URL_PATH) ?: '';
  if ($path && $baseUrl !== '' && str_starts_with($candidate, $baseUrl)) { $path = substr($candidate, strlen($baseUrl)); }
  $fs = $_SERVER['DOCUMENT_ROOT'] ?? '';
  if ($fs && $path && is_file(rtrim($fs, '/') . '/' . ltrim($path, '/'))) { return $candidate; }
  return '';
};
$renderArticleHtml = static function (string $html): string {
  $html = trim((string)$html);
  if ($html === '') { return '<p>لا يوجد محتوى لهذا الخبر.</p>'; }

  $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  if (strpos($decoded, '<') !== false && substr_count($decoded, '<') >= substr_count($html, '<')) {
    $html = $decoded;
  }

  $html = preg_replace('~<!--([\s\S]*?)-->~', '', $html);
  $html = preg_replace('~<(?:/?o:p|/?xml[^>]*)>~i', '', $html);
  $html = preg_replace("/\\s*mso-[^:;]+:[^;\"'\']+;?/i", '', $html);
  $html = preg_replace("/\\s*font-family:[^;\"'\']+;?/i", '', $html);
  $html = preg_replace("/\\s*margin(?:-[a-z]+)?:[^;\"'\']+;?/i", '', $html);
  $html = preg_replace("/\\s*text-indent:[^;\"'\']+;?/i", '', $html);
  $html = preg_replace('~<span[^>]*>\s*</span>~i', '', $html);

  $hasBlocks = preg_match('~<(p|div|h1|h2|h3|h4|ul|ol|li|table|blockquote|figure|img|br)\b~i', $html) === 1;
  if (!$hasBlocks) {
    $text = preg_replace('/[ 	]+/u', ' ', str_replace(["\r\n", "\r"], "\n", strip_tags($html)));
    $blocks = preg_split('/\n{2,}/u', trim($text)) ?: [];
    $out = [];
    foreach ($blocks as $block) {
      $block = trim($block);
      if ($block === '') continue;
      $lines = preg_split('/\n+/u', $block) ?: [];
      $listType = null;
      foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if (preg_match('/^[\-*•]\s+(.+)$/u', $line, $m)) {
          if ($listType !== 'ul') { if ($listType) $out[] = '</' . $listType . '>'; $listType = 'ul'; $out[] = '<ul>'; }
          $out[] = '<li>' . h($m[1]) . '</li>';
          continue;
        }
        if (preg_match('/^\d+[\)\.\-]\s+(.+)$/u', $line, $m)) {
          if ($listType !== 'ol') { if ($listType) $out[] = '</' . $listType . '>'; $listType = 'ol'; $out[] = '<ol>'; }
          $out[] = '<li>' . h($m[1]) . '</li>';
          continue;
        }
        if ($listType) { $out[] = '</' . $listType . '>'; $listType = null; }
        $out[] = '<p>' . h($line) . '</p>';
      }
      if ($listType) { $out[] = '</' . $listType . '>'; }
    }
    $html = implode("\n", $out);
  }

  if (!class_exists('DOMDocument')) { return $html; }
  libxml_use_internal_errors(true);
  $dom = new DOMDocument('1.0', 'UTF-8');
  $wrapped = '<!DOCTYPE html><html><body><div id="gdy-root">' . $html . '</div></body></html>';
  if (!$dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
    return $html;
  }
  $xpath = new DOMXPath($dom);
  foreach ($xpath->query('//*[@style]') as $node) {
    if ($node instanceof DOMElement) {
      $style = preg_replace('~(?:mso-[^:;]+:[^;]+;?)~i', '', (string)$node->getAttribute('style'));
      $style = preg_replace('~font-family:[^;]+;?~i', '', $style);
      $style = trim((string)$style, '; ');
      if ($style === '') $node->removeAttribute('style'); else $node->setAttribute('style', $style);
    }
  }
  foreach ($xpath->query('//table') as $table) {
    if ($table instanceof DOMElement) {
      $parent = $table->parentNode;
      if (!($parent instanceof DOMElement && strpos(' ' . $parent->getAttribute('class') . ' ', ' gdy-table-wrap ') !== false)) {
        $wrap = $dom->createElement('div');
        $wrap->setAttribute('class', 'gdy-table-wrap');
        $parent->insertBefore($wrap, $table);
        $wrap->appendChild($table);
      }
    }
  }
  foreach ($xpath->query('//img') as $img) {
    if ($img instanceof DOMElement) {
      if (!$img->hasAttribute('loading')) $img->setAttribute('loading', 'lazy');
      if (!$img->hasAttribute('decoding')) $img->setAttribute('decoding', 'async');
      if (!$img->hasAttribute('alt')) $img->setAttribute('alt', '');
    }
  }
  $root = $dom->getElementById('gdy-root');
  $out = '';
  if ($root) { foreach ($root->childNodes as $child) { $out .= $dom->saveHTML($child); } }
  libxml_clear_errors();
  return $out ?: $html;
};

$featuredWebp = $resolveWebp($image);
?>
<style>
.gdy-news-single{max-width:960px;margin:24px auto;padding:0 14px}
.gdy-news-card{background:#fff;border:1px solid rgba(148,163,184,.18);border-radius:18px;overflow:hidden;box-shadow:0 12px 34px rgba(15,23,42,.08)}
.gdy-news-cover picture,.gdy-news-cover img{width:100%;display:block}.gdy-news-cover img{max-height:460px;object-fit:cover}
.gdy-news-body{padding:24px 28px}
.gdy-news-meta{font-size:13px;color:#6b7280;margin-bottom:10px}
.gdy-news-title{margin:0 0 18px;font-size:clamp(28px,4vw,42px);line-height:1.45;color:#0f172a}
.gdy-article-body{font-size:18px;line-height:2;color:#111827}
.gdy-article-body p{margin:0 0 1rem}
.gdy-article-body h1,.gdy-article-body h2,.gdy-article-body h3,.gdy-article-body h4{margin:1.4rem 0 .75rem;line-height:1.45;color:#0f172a}
.gdy-article-body ul,.gdy-article-body ol{padding-inline-start:1.3rem;margin:0 0 1rem}
.gdy-article-body li{margin:.35rem 0}
.gdy-article-body .gdy-table-wrap,.gdy-article-body .table-wrap{overflow:auto;margin:1rem 0}
.gdy-article-body table{width:100%;border-collapse:collapse;margin:1rem 0;background:#fff}
.gdy-article-body td,.gdy-article-body th{border:1px solid #d1d5db;padding:10px;vertical-align:top}
.gdy-article-body a{color:#2563eb;text-decoration:underline}
.gdy-article-body figure{margin:1.2rem auto;text-align:center}
.gdy-article-body figure.align-right{max-width:56%;margin-inline-start:auto}
.gdy-article-body figure.align-left{max-width:56%;margin-inline-end:auto}
.gdy-article-body figure.align-wide,.gdy-article-body figure.size-full{max-width:100%}
.gdy-article-body figure.size-small{max-width:38%}
.gdy-article-body figure.size-large{max-width:78%}
.gdy-article-body figcaption{font-size:13px;color:#64748b;margin-top:.35rem}
.gdy-article-body img{max-width:100%;height:auto;border-radius:14px;display:block;margin:1rem auto}
.gdy-article-body blockquote{margin:1rem 0;padding:.9rem 1rem;border-inline-start:4px solid #cbd5e1;background:#f8fafc;color:#334155}
.gdy-comments{margin-top:24px;background:#fff;border:1px solid rgba(148,163,184,.18);border-radius:18px;padding:24px 28px;box-shadow:0 12px 34px rgba(15,23,42,.08)}
.gdy-comments h2{margin:0 0 16px;font-size:22px}
.gdy-comment-form{display:grid;gap:12px;margin-bottom:18px}
.gdy-comment-form textarea{min-height:110px;padding:12px;border:1px solid #cbd5e1;border-radius:12px;font:inherit}
.gdy-comment-form button{padding:10px 16px;border:0;border-radius:10px;background:#111827;color:#fff;cursor:pointer}
.gdy-comment-card{padding:14px;border:1px solid #e5e7eb;border-radius:14px;background:#f8fafc}
@media (max-width:768px){.gdy-article-body figure.align-right,.gdy-article-body figure.align-left,.gdy-article-body figure.size-small,.gdy-article-body figure.size-large{max-width:100%}}
.gdy-comment-head{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:8px;flex-wrap:wrap}
.gdy-comment-time{font-size:12px;color:#6b7280}
</style>
<section class="gdy-news-single">
  <nav style="font-size:14px;margin-bottom:16px;color:#6b7280"><a href="<?= h(function_exists('gdy_route_home_url') ? gdy_route_home_url($lang) : ($navBaseUrl . '/')) ?>" style="color:inherit;text-decoration:none"><?= h(__('nav.home', 'الرئيسية')) ?></a> / <a href="<?= h($navBaseUrl . '/news') ?>" style="color:inherit;text-decoration:none"><?= h(__('nav.news', 'الأخبار')) ?></a></nav>
  <article class="gdy-news-card">
    <?php if ($image !== ''): ?><div class="gdy-news-cover"><picture><?php if ($featuredWebp !== ''): ?><source srcset="<?= h($featuredWebp) ?>" type="image/webp"><?php endif; ?><img src="<?= h($image) ?>" alt="<?= h($title) ?>" loading="eager" fetchpriority="high" decoding="async"></picture></div><?php endif; ?>
    <div class="gdy-news-body">
      <?php if ($date !== ''): ?><div class="gdy-news-meta"><?= h(date('Y-m-d', strtotime($date)) ?: $date) ?></div><?php endif; ?>
      <h1 class="gdy-news-title"><?= h($title) ?></h1>
      <div class="gdy-article-body article-body"><?= $renderArticleHtml($body) ?></div>
    </div>
  </article>

  <section id="comments" class="gdy-comments">
    <h2><?= h(__('comments.title', 'التعليقات')) ?> (<?= (int)count($comments) ?>)</h2>
    <?php if ($userLoggedIn): ?>
      <form class="gdy-comment-form" method="POST" action="<?= h($navBaseUrl . '/comment/add') ?>" data-gdy-once="1">
        <?php if (function_exists('csrf_field')) { echo csrf_field(); } ?>
        <input type="hidden" name="news_id" value="<?= (int)$id ?>">
        <label for="comment-content" style="display:block;margin-bottom:6px;color:#334155;font-size:14px"><?= h(__('comments.placeholder', 'أضف تعليقك...')) ?></label><textarea id="comment-content" name="content" required placeholder="<?= h(__('comments.placeholder', 'أضف تعليقك...')) ?>"></textarea>
        <div><button type="submit"><?= h(__('comments.submit', 'نشر التعليق')) ?></button></div>
      </form>
    <?php else: ?>
      <p style="margin:0 0 18px;color:#4b5563"><?= h(__('comments.login_required_prefix', 'يجب')) ?> <a href="<?= h($navBaseUrl . '/login') ?>"><?= h(__('auth.login', 'تسجيل الدخول')) ?></a> <?= h(__('comments.login_required_suffix', 'لإضافة تعليق')) ?></p>
    <?php endif; ?>

    <?php if ($comments): ?>
      <div style="display:grid;gap:14px">
        <?php foreach ($comments as $comment): ?>
          <article class="gdy-comment-card">
            <div class="gdy-comment-head">
              <strong><?= h((string)($comment['author_name'] ?? __('comments.guest', 'زائر'))) ?></strong>
              <span class="gdy-comment-time"><?= h((string)($comment['created_at'] ?? '')) ?></span>
            </div>
            <div style="white-space:pre-wrap;line-height:1.9"><?= nl2br(h((string)($comment['content'] ?? ''))) ?></div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div style="color:#6b7280"><?= h(__('comments.empty', 'لا توجد تعليقات بعد. كن أول من يعلّق.')) ?></div>
    <?php endif; ?>
  </section>
</section>
<?php
$schemaNews = [
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => (string)($title ?? ''),
    'datePublished' => (string)($date ?? ''),
    'image' => (string)($image ?? ''),
];
?>
<script type="application/ld+json"><?= json_encode($schemaNews, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>