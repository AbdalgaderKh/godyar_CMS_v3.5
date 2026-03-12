<?php
if (!function_exists('gdy_e')) {
    function gdy_e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('gdy_tr')) {
    function gdy_tr($type, $id, $field, $fallback = null) { return $fallback; }
}
if (!function_exists('gdy_abs_url')) {
    function gdy_abs_url(string $baseUrl, ?string $path): string {
        $path = trim((string)$path);
        if ($path === '') return '';
        if (preg_match('~^https?://~i', $path)) return $path;
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}
$baseUrl = rtrim((string)($baseUrl ?? ''), '/');
$navBaseUrl = rtrim((string)($navBaseUrl ?? $baseUrl), '/');
$lang = (string)($pageLang ?? (function_exists('gdy_current_lang') ? gdy_current_lang() : 'ar'));
$pageTitle = (string)($pageTitle ?? $meta_title ?? ($category['name'] ?? ''));

$category = is_array($category ?? null) ? $category : [];
$categories = is_array($categories ?? null) ? $categories : [];
$news = $news ?? ($newsItems ?? ($newsList ?? ($items ?? [])));
$news = is_array($news) ? $news : [];
$catName = (string)(gdy_tr('category', $category['id'] ?? 0, 'name', $category['name'] ?? (__('category.default', 'القسم'))) ?? ($category['name'] ?? (__('category.default', 'القسم'))));
$catSlug = (string)($category['slug'] ?? '');
$currentCategoryUrl = (string)($currentCategoryUrl ?? ($catSlug !== '' ? ($navBaseUrl . '/category/' . rawurlencode($catSlug)) : ($navBaseUrl . '/category')));
$currentPage = max(1, (int)($currentPage ?? 1));
$pages = max(1, (int)($pages ?? 1));
$fmtDate = static function ($dt): string {
    $s = trim((string)$dt);
    if ($s === '') return '';
    $ts = strtotime($s);
    return $ts ? date('Y-m-d', $ts) : $s;
};
$excerpt = static function (array $item): string {
    $t = (string)($item['excerpt'] ?? $item['summary'] ?? $item['content'] ?? '');
    $t = trim(preg_replace('/\s+/u', ' ', strip_tags($t)) ?? '');
    if ($t === '') return '';
    return function_exists('mb_substr') ? mb_substr($t, 0, 160, 'UTF-8') . (mb_strlen($t, 'UTF-8') > 160 ? '…' : '') : substr($t, 0, 160);
};
?>
<section class="container" style="padding-top:24px;padding-bottom:32px;">
  <div class="category-head" style="margin-bottom:24px;padding:20px;border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(0,0,0,.05)">
    <h1 class="category-title" style="margin:0 0 8px;font-size:28px;font-weight:700;"><?= gdy_e($catName) ?></h1>
    <?php if (!empty($category['description'])): ?>
      <p class="category-desc" style="margin:0;color:#6b7280"><?= gdy_e((string)$category['description']) ?></p>
    <?php endif; ?>
  </div>

  <div style="display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:24px;align-items:start;">
    <div>
      <?php if ($news): ?>
        <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px;">
          <?php foreach ($news as $item): $item = is_array($item) ? $item : []; $url = (string)($item['url'] ?? ''); if ($url === '') { $nid = (int)($item['id'] ?? 0); $nslug = trim((string)($item['slug'] ?? '')); $url = $nid > 0 ? ($navBaseUrl . '/news/id/' . $nid) : ($navBaseUrl . '/news/' . rawurlencode($nslug)); } $image = gdy_abs_url($baseUrl, (string)($item['featured_image'] ?? $item['image'] ?? $item['image_path'] ?? $item['thumbnail'] ?? '')); $title = (string)($item['title'] ?? (__('news.untitled', 'خبر بدون عنوان'))); ?>
            <article class="card" style="background:#fff;border:1px solid 
              <?php if ($image !== ''): ?><a href="<?= gdy_e($url) ?>"><?php endif; ?>
                <?php if ($image !== ''): ?><img src="<?= gdy_e($image) ?>" alt="<?= gdy_e($title) ?>" loading="lazy" style="width:100%;height:190px;object-fit:cover;display:block"><?php endif; ?>
              <?php if ($image !== ''): ?></a><?php endif; ?>
              <div class="card-body" style="padding:16px">
                <div class="meta" style="font-size:12px;color:#6b7280;margin-bottom:8px"><?= gdy_e($fmtDate($item['publish_at'] ?? $item['published_at'] ?? $item['created_at'] ?? '')) ?></div>
                <h2 class="title" style="font-size:18px;font-weight:700;line-height:1.5;margin:0 0 10px"><a href="<?= gdy_e($url) ?>" style="text-decoration:none;color:inherit"><?= gdy_e($title) ?></a></h2>
                <?php $ex = $excerpt($item); if ($ex !== ''): ?><div class="excerpt" style="font-size:14px;line-height:1.8;color:#4b5563"><?= gdy_e($ex) ?></div><?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty" style="padding:24px;border-radius:16px;background:#fff;border:1px dashed 
      <?php endif; ?>
    </div>
    <aside>
      <?php if ($categories): ?>
        <section style="background:#fff;border:1px solid 
          <div style="margin-bottom:12px" class="as-block as-block-hd"><h2 class="as-block-title" style="font-size:18px;margin:0"><?= gdy_e(__('category.sections', 'الأقسام')) ?></h2></div>
          <div class="p-3" style="display:flex;flex-wrap:wrap;gap:10px">
            <?php foreach ($categories as $c): if (!is_array($c)) continue; $slug=(string)($c['slug'] ?? ''); if ($slug==='') continue; $href=$navBaseUrl.'/category/'.rawurlencode($slug); $active=$slug===$catSlug; ?>
              <a class="as-pill<?= $active ? ' is-active' : '' ?>" href="<?= gdy_e($href) ?>" style="display:inline-block;padding:8px 12px;border-radius:999px;border:1px solid 
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
    </aside>
  </div>
</section>
