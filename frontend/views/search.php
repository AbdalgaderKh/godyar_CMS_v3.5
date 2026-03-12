<?php

$__header = __DIR__ . '/partials/header.php';
$__footer = __DIR__ . '/partials/footer.php';

$GLOBALS['isSearchPage'] = true;

if (is_file($__header) === true) { require $__header; }

if (function_exists('h') === false) {
    function h(?string $v): string { return htmlspecialchars((string)$v ?? '', ENT_QUOTES, 'UTF-8'); }
}
if (function_exists('highlight_term') === false) {
    function highlight_term(string $text, string $term): string {
        $term = trim((string)$term);
        if ($term === '') return h($text);
        $safe = h($text);
        return preg_replace('/' .preg_quote($term, '/') . '/iu', '<mark class="search-hl">$0</mark>', $safe);
    }
}

$cspNonce = defined('GDY_CSP_NONCE') ? (string)GDY_CSP_NONCE : '';
$nonceAttr = ($cspNonce !== '') ? ' nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"' : '';

$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$typeFilter = (string)($_GET['type'] ?? 'all');
$catFilter = (string)($_GET['cat'] ?? 'all');
$dateFilter = (string)($_GET['date'] ?? 'any');
$match = (string)($_GET['match'] ?? 'all');

$results = $results ?? [];
$total = (int)($total ?? 0);
$pages = (int)($pages ?? 0);
$counts = $counts ?? ['news' => 0,'pages' => 0,'authors' => 0];
$categories = $categories ?? [];

$baseUrl = rtrim((string)($baseUrl ?? ''), '/');

$https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
 || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolayer((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
 || (!empty($_SERVER['SERVER_PORT']) && (string)($_SERVER['SERVER_PORT']) === '443');
$scheme = $https ? 'https' : 'http';
$currentUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
?>
<div class = "container" style = "padding-top:18px;padding-bottom:28px;direction:rtl">
    <div class = "d-flex align-items-center justify-content-between flex-wrap" style = "gap:10px">
        <div>
            <h1 style = "margin:0;font-size:1.35rem">البحث</h1>
            <?php if ($q !== ''): ?>
                <div class = "search-meta">نتائج البحث عن: <strong><?php echo h($q); ?></strong> • إجمالي النتائج: <strong><?php echo (int)$total; ?></strong></div>
            <?php else: ?>
                <div class = "search-meta">اكتب كلمة للبحث في محتوى الموقع . </div>
            <?php endif; ?>
        </div>

        <div class = "search-actions">
            <button data-copy-url = "<?php echo h($currentUrl); ?>" data-copy-success = "تم نسخ رابط البحث" type = "button" class = "btn btn-sm btn-outline-secondary" >نسخ رابط البحث</button>
            <a class = "btn btn-sm btn-outline-secondary" href = "<?php echo h($baseUrl); ?>/ar/search">مسح البحث</a>
        </div>
    </div>

    <form method = "get" action = "<?php echo h($baseUrl); ?>/ar/search" class = "mt-3">
        <div class = "row g-2">
            <div class = "col-12 col-lg-6">
	                <label class="visually-hidden" for="searchQ">بحث</label>
	                <input id="searchQ" name = "q" value = "<?php echo h($q); ?>" class = "form-control" placeholder = "ابحث في الأخبار والصفحات وكتّاب الرأي..." autocomplete="off" inputmode="search" aria-label="بحث" />
            </div>
            <div class = "col-6 col-lg-2">
                <select class = "form-select" name = "type">
                    <option value = "all"     <?php echo $typeFilter==='all'?'selected':''; ?>>الكل</option>
                    <option value = "news"    <?php echo $typeFilter==='news'?'selected':''; ?>>أخبار</option>
                    <option value = "opinion" <?php echo $typeFilter==='opinion'?'selected':''; ?>>مقالات رأي</option>
                    <option value = "page"    <?php echo $typeFilter==='page'?'selected':''; ?>>صفحات</option>
                    <option value = "author"  <?php echo $typeFilter==='author'?'selected':''; ?>>كتّاب الرأي</option>
                </select>
            </div>
            <div class = "col-6 col-lg-2">
                <select class = "form-select" name = "cat">
                    <option value = "all">كل الأقسام</option>
                    <?php foreach ($categories as $c): ?>
                        <?php $slug = (string)($c['slug'] ?? ''); $name = (string)($c['name'] ?? ''); if ($slug==='') continue; ?>
                        <option value = "<?php echo h($slug); ?>" <?php echo $catFilter===$slug?'selected':''; ?>><?php echo h($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class = "col-6 col-lg-1">
                <select class = "form-select" name = "date">
                    <option value = "any" <?php echo $dateFilter==='any'?'selected':''; ?>>أي وقت</option>
                    <option value = "24h" <?php echo $dateFilter==='24h'?'selected':''; ?>>آخر 24 ساعة</option>
                    <option value = "7d"  <?php echo $dateFilter==='7d'?'selected':''; ?>>7 أيام</option>
                    <option value = "30d" <?php echo $dateFilter==='30d'?'selected':''; ?>>30 يومًا</option>
                    <option value = "year"<?php echo $dateFilter==='year'?'selected':''; ?>>سنة</option>
                </select>
            </div>
            <div class = "col-6 col-lg-1">
                <select class = "form-select" name = "match">
                    <option value = "all" <?php echo $match==='all'?'selected':''; ?>>كل الكلمات</option>
                    <option value = "any" <?php echo $match==='any'?'selected':''; ?>>أي كلمة</option>
                </select>
            </div>

            <div class = "col-12">
                <button class = "btn btn-primary w-100">بحث</button>
            </div>
        </div>
    </form>

    <div class = "search-tabs">
        <?php
            $mk = static function (string $t, string $label, int $cnt) use ($baseUrl, $q, $catFilter, $dateFilter, $match, $typeFilter): string {
                $qs = http_build_query(['q' => $q,'type' => $t,'cat' => $catFilter,'date' => $dateFilter,'match' => $match]);
                $href = rtrim($baseUrl,'/') . '/ar/search?' . $qs;
                $active = ($typeFilter === $t) ? 'active' : '';
                return '<a class="' . $active . '" href="' .h($href) . '">' .h($label) . ' <span class="badge bg-secondary">' . (int)$cnt . '</span></a>';
            };
            echo $mk('all','الكل', (int)$total);
            echo $mk('news','أخبار', (int)($counts['news'] ?? 0));
            echo $mk('page','صفحات', (int)($counts['pages'] ?? 0));
            echo $mk('author','كتّاب الرأي', (int)($counts['authors'] ?? 0));
        ?>
    </div>

	    <div class = "mt-2">
	        <label class="visually-hidden" for="inPageFilter">فلترة داخل الصفحة</label>
	        <input id = "inPageFilter" class = "form-control" placeholder = "فلترة داخل هذه الصفحة..." autocomplete="off" aria-label="فلترة داخل الصفحة"     />
        <small class = "text-muted">تلميح: اكتب كلمة لتصفية النتائج المعروضة فقط (بدون إعادة البحث) . </small>
    </div>

    <div class = "mt-3" id = "resultsWrap">
        <?php if (!$results): ?>
            <div class = "alert alert-warning" style = "border-radius:14px">
                لا توجد نتائج مطابقة لعبارة البحث <strong><?php echo h($q); ?></strong> .
                <div class = "mt-2">جرّب كلمات أقصر، أو اختر "أي كلمة"، أو أزل فلتر القسم . </div>
            </div>
        <?php else: ?>
            <div class = "row g-3" id = "resultsGrid">
                <?php foreach ($results as $r): ?>
                    <?php
                        $kind = (string)($r['kind'] ?? 'news');
                        $title = (string)($r['title'] ?? '');
                        $url = (string)($r['url'] ?? '');
                        $img = (string)($r['image'] ?? '');
                        $excerpt = (string)($r['excerpt'] ?? '');
                        $dateStr = (string)($r['created_at'] ?? '');
                        $date = $dateStr !== '' ? date('Y-m-d', strtotime($dateStr)) : '';
                        $badge = ($kind==='page') ? 'صفحة' : (($kind==='author') ? 'كاتب رأي' : 'خبر');
                        $href = $url !== '' ? (rtrim($baseUrl,'/') . $url) : '#';
                    ?>
                    <div class = "col-12">
                        <a href = "<?php echo h($href); ?>" style = "color:inherit;text-decoration:none" class = "result-item">
                            <div class = "search-card">
                                <?php if ($img !== ''): ?>
                                    <img loading="lazy" decoding="async" class = "search-thumb" src = "<?php echo h($img); ?>" alt = "<?php echo h($title); ?>" />
                                <?php else: ?>
                                    <div class = "search-thumb"></div>
                                <?php endif; ?>
                                <div style = "min-width:0">
                                    <div class = "search-meta">
                                        <span class = "badge bg-primary"><?php echo h($badge); ?></span>
                                        <?php if ($date !== ''): ?><span><?php echo h($date); ?></span><?php endif; ?>
                                        <?php if (!empty($r['category_slug'] ?? '')): ?><span>#<?php echo h((string)$r['category_slug']); ?></span><?php endif; ?>
                                    </div>
                                    <div style = "font-size:1.05rem;font-weight:700;margin-top:4px">
                                        <?php echo highlight_term($title, $q); ?>
                                    </div>
                                    <?php if ($excerpt !== ''): ?>
                                        <div style = "opacity:.9;margin-top:6px;line-height:1.7">
                                            <?php echo highlight_term(mb_substr(strip_tags($excerpt), 0, 220), $q); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($pages > 1): ?>
                <nav class = "mt-4" aria-label = "pagination">
                    <ul class = "pagination justify-content-center">
                        <?php
                            $params = $_GET;
                            for ($p = 1; $p <= $pages; $p++):
                                $params['page'] = $p;
                                $href = rtrim($baseUrl,'/') . '/ar/search?' .http_build_query($params);
                        ?>
                            <li class = "page-item <?php echo $p===$page?'active':''; ?>"><a class = "page-link" href = "<?php echo h($href); ?>"><?php echo (int)$p; ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

	<?php $cspNonce = defined('GDY_CSP_NONCE') ? (string)GDY_CSP_NONCE : ''; ?>
	<?php if (is_file($__footer)) { require $__footer; } ?>