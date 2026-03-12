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

$currentPage = 'html_auto_fixer';
$pageTitle = 'Godyar HTML Auto Fixer';

if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$ROOT = realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2);

function norm_path(string $path): string {
    return str_replace('\\', '/', $path);
}

function rel_path(string $path, string $root): string {
    $path = norm_path($path);
    $root = rtrim(norm_path($root), '/');
    return ltrim(str_replace($root, '', $path), '/');
}

function ensure_dir(string $dir): bool {
    return is_dir($dir) || @mkdir($dir, 0755, true);
}

function safe_get(string $file): string {
    $c = @file_get_contents($file);
    return is_string($c) ? $c : '';
}

function safe_put(string $file, string $content): bool {
    return file_put_contents($file, $content) !== false;
}

function backup_file(string $file, string $root): ?string {
    if (!is_file($file)) return null;
    $dir = $root . '/_html_auto_fixer_backups/' . date('Ymd_His');
    ensure_dir($dir);
    $target = $dir . '/' . str_replace('/', '__', rel_path($file, $root));
    return @copy($file, $target) ? $target : null;
}

function find_candidate_files(string $root): array {
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    $files = [];
    foreach ($rii as $file) {
        /** @var SplFileInfo $file */
        if (!$file->isFile()) continue;
        $path = $file->getPathname();
        $rel = rel_path($path, $root);

        if (preg_match('~(^|/)(vendor|node_modules|\.git|storage|cache|logs|uploads|_phase\d+_backups|_html_auto_fixer_backups|backups?|1)(/|$)~i', $rel)) {
            continue;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['php', 'html'], true)) {
            continue;
        }

        $files[] = $path;
    }

    sort($files);
    return $files;
}

function merge_duplicate_class_attributes(string $html): array {
    $count = 0;

    $html = preg_replace_callback(
        '~<([a-zA-Z][a-zA-Z0-9:-]*)([^>]*)>~s',
        function ($m) use (&$count) {
            $tag = $m[1];
            $attrs = $m[2];

            if (substr(trim($attrs), -1) === '/') {
                $selfClose = ' /';
                $attrs = preg_replace('~/\s*$~', '', $attrs);
            } else {
                $selfClose = '';
            }

            if (!preg_match_all('/\sclass\s*=\s*("|\')(.*?)\1/is', $attrs, $mm, PREG_SET_ORDER)) {
                return '<' . $tag . $attrs . $selfClose . '>';
            }

            if (count($mm) < 2) {
                return '<' . $tag . $attrs . $selfClose . '>';
            }

            $classes = [];
            foreach ($mm as $one) {
                $parts = preg_split('/\s+/', trim((string)$one[2])) ?: [];
                foreach ($parts as $p) {
                    $p = trim($p);
                    if ($p !== '' && !in_array($p, $classes, true)) {
                        $classes[] = $p;
                    }
                }
            }

            $attrs = preg_replace('/\sclass\s*=\s*("|\')(.*?)\1/is', '', $attrs);
            $attrs .= ' class="' . implode(' ', $classes) . '"';
            $count++;

            return '<' . $tag . $attrs . $selfClose . '>';
        },
        $html
    );

    return [$html, $count];
}

function fix_empty_aria_labels(string $html): array {
    $count = 0;
    $html = preg_replace_callback(
        '/\saria-label\s*=\s*("|\')\s*\1/i',
        function () use (&$count) {
            $count++;
            return '';
        },
        $html
    );
    return [$html, $count];
}

function fix_main_role(string $html): array {
    $count = 0;
    $html = preg_replace_callback(
        '/<main\b([^>]*)\srole\s*=\s*("|\')main\2([^>]*)>/i',
        function ($m) use (&$count) {
            $count++;
            return '<main' . $m[1] . $m[3] . '>';
        },
        $html
    );
    return [$html, $count];
}

function fix_sitemap_rel(string $html): array {
    $count = 0;
    $html = preg_replace_callback(
        '/<link\b([^>]*?)\brel\s*=\s*("|\')sitemap\2([^>]*?)>/i',
        function ($m) use (&$count) {
            $count++;
            return '<link' . $m[1] . ' rel="alternate"' . $m[3] . '>';
        },
        $html
    );
    return [$html, $count];
}

function fix_div_inside_span(string $html): array {
    $count = 0;

    $pattern = '~<span([^>]*)>\s*((?:<div\b[^>]*>.*?</div>\s*)+)</span>~is';
    $html = preg_replace_callback(
        $pattern,
        function ($m) use (&$count) {
            $count++;
            return '<div' . $m[1] . '>' . $m[2] . '</div>';
        },
        $html
    );

    return [$html, $count];
}

function remove_empty_headings(string $html): array {
    $count = 0;

    $html = preg_replace_callback(
        '~<h([1-6])\b([^>]*)>\s*(?:&nbsp;|\x{00A0}|\s|<span[^>]*>\s*</span>)*</h\1>~iu',
        function () use (&$count) {
            $count++;
            return '';
        },
        $html
    );

    return [$html, $count];
}

function fix_div_aria_without_role(string $html): array {
    $count = 0;

    $html = preg_replace_callback(
        '~<div\b([^>]*?)\saria-label\s*=\s*("|\')(.*?)\2([^>]*)>~is',
        function ($m) use (&$count) {
            $before = $m[1];
            $label  = $m[3];
            $after  = $m[4];
            $fullAttrs = $before . $after;

            if (preg_match('/\srole\s*=/i', $fullAttrs)) {
                return '<div' . $before . ' aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;"' . $after . ' role="region">';
            }

            if (trim($label) === '') {
                $count++;
                return '<div' . $fullAttrs . '>';
            }

            $count++;
            return '<div' . $before . ' aria-label="&#039; . htmlspecialchars($label, ENT_QUOTES, &#039;UTF-8&#039;) . &#039;"' . $after . ' role="region">';
        },
        $html
    );

    return [$html, $count];
}

function try_add_hidden_heading_to_sections(string $html): array {
    $count = 0;

    $html = preg_replace_callback(
        '~<section\b([^>]*)class\s*=\s*("|\')([^"\']*)(gdy-footer-card|footer-card)([^"\']*)\2([^>]*)>(?!\s*<h[1-6]\b)~is',
        function ($m) use (&$count) {
            $count++;
            return '<section' . $m[1] . 'class="' . trim($m[3] . $m[4] . $m[5]) . '"' . $m[6] . '><h2 class="visually-hidden">Section</h2>';
        },
        $html
    );

    return [$html, $count];
}

function ensure_charset_first_in_head(string $html): array {
    $count = 0;

    if (!preg_match('~<head\b[^>]*>(.*?)</head>~is', $html, $m)) {
        return [$html, $count];
    }

    $headInner = $m[1];
    if (!preg_match('~<meta\s+charset\s*=\s*("|\')?utf-8\1?\s*/?>~i', $headInner, $mc)) {
        return [$html, $count];
    }

    $metaTag = $mc[0];
    $newHeadInner = preg_replace('~<meta\s+charset\s*=\s*("|\')?utf-8\1?\s*/?>~i', '', $headInner, 1);
    $newHeadInner = ltrim($newHeadInner);
    $newHeadInner = $metaTag . "\n" . $newHeadInner;

    $html = preg_replace('~<head\b[^>]*>.*?</head>~is', '<head>' . $newHeadInner . '</head>', $html, 1);
    $count++;

    return [$html, $count];
}

function run_fixes(string $content): array {
    $summary = [
        'charset_first' => 0,
        'main_role_removed' => 0,
        'empty_aria_removed' => 0,
        'div_aria_role_fixed' => 0,
        'sitemap_rel_fixed' => 0,
        'duplicate_class_merged' => 0,
        'div_inside_span_fixed' => 0,
        'empty_headings_removed' => 0,
        'footer_section_heading_added' => 0,
    ];

    [$content, $n] = ensure_charset_first_in_head($content);
    $summary['charset_first'] += $n;

    [$content, $n] = fix_main_role($content);
    $summary['main_role_removed'] += $n;

    [$content, $n] = fix_empty_aria_labels($content);
    $summary['empty_aria_removed'] += $n;

    [$content, $n] = fix_div_aria_without_role($content);
    $summary['div_aria_role_fixed'] += $n;

    [$content, $n] = fix_sitemap_rel($content);
    $summary['sitemap_rel_fixed'] += $n;

    [$content, $n] = merge_duplicate_class_attributes($content);
    $summary['duplicate_class_merged'] += $n;

    [$content, $n] = fix_div_inside_span($content);
    $summary['div_inside_span_fixed'] += $n;

    [$content, $n] = remove_empty_headings($content);
    $summary['empty_headings_removed'] += $n;

    [$content, $n] = try_add_hidden_heading_to_sections($content);
    $summary['footer_section_heading_added'] += $n;

    return [$content, $summary];
}

$mode = (string)($_POST['mode'] ?? '');
$selectedFiles = isset($_POST['files']) && is_array($_POST['files']) ? array_values($_POST['files']) : [];
$allFiles = find_candidate_files($ROOT);

$report = [];
$totalSummary = [
    'charset_first' => 0,
    'main_role_removed' => 0,
    'empty_aria_removed' => 0,
    'div_aria_role_fixed' => 0,
    'sitemap_rel_fixed' => 0,
    'duplicate_class_merged' => 0,
    'div_inside_span_fixed' => 0,
    'empty_headings_removed' => 0,
    'footer_section_heading_added' => 0,
];

if ($mode === 'fix' && $selectedFiles) {
    foreach ($selectedFiles as $rel) {
        $path = $ROOT . '/' . ltrim($rel, '/');
        if (!is_file($path)) {
            $report[] = ['file' => $rel, 'status' => 'missing', 'summary' => []];
            continue;
        }

        $old = safe_get($path);
        if ($old === '') {
            $report[] = ['file' => $rel, 'status' => 'unreadable', 'summary' => []];
            continue;
        }

        [$new, $summary] = run_fixes($old);

        $changed = ($new !== $old);
        if ($changed) {
            backup_file($path, $ROOT);
            safe_put($path, $new);
        }

        foreach ($totalSummary as $k => $v) {
            $totalSummary[$k] += (int)($summary[$k] ?? 0);
        }

        $report[] = [
            'file' => $rel,
            'status' => $changed ? 'fixed' : 'no-change',
            'summary' => $summary,
        ];
    }
}

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../layout/sidebar.php';
?>
<div class="admin-content">
  <div class="container-fluid" style="max-width:1200px;margin:24px auto;">
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden">
      <div style="padding:16px 20px;background:#0f172a;color:#fff;font-weight:700">Godyar HTML Auto Fixer</div>
      <div style="padding:16px 20px">

        <div style="margin-bottom:18px;padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#f8fafc;">
          <strong>الإصلاحات التي تنفذها الأداة:</strong>
          <ul style="margin:10px 0 0;padding-right:18px;line-height:1.9;">
            <li>نقل meta charset لأول head إن أمكن</li>
            <li>إزالة role="main" من main</li>
            <li>حذف aria-label الفارغة</li>
            <li>إضافة role="region" إلى div التي تحمل aria-label</li>
            <li>إصلاح rel="sitemap"</li>
            <li>دمج class المكررة</li>
            <li>استبدال span التي تحتوي div إلى div</li>
            <li>حذف headings الفارغة</li>
            <li>إضافة heading مخفي لبعض أقسام footer</li>
          </ul>
        </div>

        <form method="post">
          <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
          <input type="hidden" name="mode" value="fix">

          <div style="margin-bottom:14px;font-weight:700;">اختر الملفات:</div>

          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:10px;max-height:460px;overflow:auto;border:1px solid #e5e7eb;padding:12px;border-radius:12px;background:#fff;">
            <?php foreach ($allFiles as $file): ?>
              <?php $rel = rel_path($file, $ROOT); ?>
              <label style="display:flex;gap:8px;align-items:flex-start;padding:8px;border:1px solid #f1f5f9;border-radius:10px;">
                <input type="checkbox" name="files[]" value="<?= h($rel) ?>">
                <span style="word-break:break-word;"><?= h($rel) ?></span>
              </label>
            <?php endforeach; ?>
          </div>

          <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
            <button type="button" class="btn btn-outline-secondary" onclick="document.querySelectorAll('input[name=&quot;files[]&quot;]').forEach(el=>el.checked=true)">تحديد الكل</button>
            <button type="button" class="btn btn-outline-secondary" onclick="document.querySelectorAll('input[name=&quot;files[]&quot;]').forEach(el=>el.checked=false)">إلغاء الكل</button>
            <button type="submit" class="btn btn-primary">تشغيل Auto Fixer</button>
          </div>
        </form>

        <?php if ($report): ?>
          <hr>
          <h3 style="margin-bottom:12px;">نتيجة التنفيذ</h3>

          <div style="margin-bottom:16px;padding:12px;border:1px solid #e5e7eb;border-radius:12px;background:#f8fafc;">
            <strong>الملخص العام:</strong>
            <div style="margin-top:8px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px;">
              <?php foreach ($totalSummary as $k => $v): ?>
                <div style="padding:8px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;">
                  <strong><?= h($k) ?></strong><br><?= (int)$v ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div style="display:grid;gap:10px;">
            <?php foreach ($report as $row): ?>
              <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fff;">
                <div style="font-weight:700;"><?= h($row['file']) ?></div>
                <div style="margin:6px 0 8px;">
                  الحالة:
                  <?php if ($row['status'] === 'fixed'): ?>
                    <span style="color:#166534;font-weight:700;">تم التعديل</span>
                  <?php elseif ($row['status'] === 'no-change'): ?>
                    <span style="color:#92400e;font-weight:700;">لا تغيير</span>
                  <?php else: ?>
                    <span style="color:#991b1b;font-weight:700;"><?= h($row['status']) ?></span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($row['summary'])): ?>
                  <div style="font-size:.92rem;color:#334155;">
                    <?php foreach ($row['summary'] as $k => $v): ?>
                      <?php if ((int)$v > 0): ?>
                        <div><?= h($k) ?>: <?= (int)$v ?></div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>