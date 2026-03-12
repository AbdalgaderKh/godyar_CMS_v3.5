<?php

$root = dirname(__DIR__);
$assetsDir = $root . '/assets';
$reportDir = $root . '/storage/reports';
@mkdir($reportDir, 0755, true);

function scan_text_files(string $root): array {
    $refs = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        
        if (!$file->isFile()) continue;
        $path = $file->getPathname();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['png','jpg','jpeg','gif','svg','woff','woff2','ttf','eot','mp3','mp4','zip','ico','pdf'], true)) continue;

        $txt = @file_get_contents($path);
        if (!is_string($txt)) continue;

        if (preg_match_all('~/?assets/[a-zA-Z0-9_\-./]+~', $txt, $m)) {
            foreach ($m[0] as $p) {
                $p = ltrim($p, '/');
                $refs[$p] = true;
            }
        }
    }
    return array_keys($refs);
}

function parse_css_urls(string $cssPath, string $cssRel): array {
    $deps = [];
    $txt = @file_get_contents($cssPath);
    if (!is_string($txt)) return $deps;

    
    if (preg_match_all('~url\(\s*([\'\"]?)([^\'\")]+)\1\s*\)~', $txt, $m, PREG_SET_ORDER)) {
        foreach ($m as $row) {
            $u = trim($row[2]);
            if ($u === '' || str_starts_with($u, 'data:') || str_contains($u, '://')) continue;
            if (str_starts_with($u, '/assets/')) $deps[ltrim($u,'/')] = true;
            elseif (str_starts_with($u, 'assets/')) $deps[$u] = true;
            else {
                $base = dirname($cssRel);
                $dep = str_replace('\\','/', $base . '/' . $u);
                $dep = preg_replace('~(/\./)~', '/', $dep);
                while (str_contains($dep, '/../')) {
                    $dep = preg_replace('~[^/]+/\.\./~', '', $dep);
                }
                if (str_starts_with($dep, 'assets/')) $deps[$dep] = true;
            }
        }
    }

    
    if (preg_match_all('~@import\s+(?:url\()?\s*[\"\']([^\"\']+)[\"\']~', $txt, $m)) {
        foreach ($m[1] as $u) {
            if (str_starts_with($u, '/assets/')) $deps[ltrim($u,'/')] = true;
            elseif (str_starts_with($u, 'assets/')) $deps[$u] = true;
        }
    }

    return array_keys($deps);
}

$refs = scan_text_files($root);
$refSet = array_fill_keys($refs, true);

$queue = [];
foreach ($refs as $r) {
    if (str_ends_with(strtolower($r), '.css')) $queue[] = $r;
}
$seen = $refSet;

while ($queue) {
    $cssRel = array_pop($queue);
    $cssPath = $root . '/' . $cssRel;
    if (!is_file($cssPath)) continue;
    foreach (parse_css_urls($cssPath, $cssRel) as $dep) {
        if (!isset($seen[$dep])) {
            $seen[$dep] = true;
            if (str_ends_with(strtolower($dep), '.css')) $queue[] = $dep;
        }
    }
}
$refSet = $seen;

$all = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($assetsDir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    
    if (!$file->isFile()) continue;
    $rel = 'assets/' . ltrim(str_replace('\\','/', substr($file->getPathname(), strlen($assetsDir))), '/');
    $all[] = $rel;
}

$used = [];
$unused = [];
foreach ($all as $a) {
    if (isset($refSet[$a])) $used[] = $a;
    else $unused[] = $a;
}

$report = [
    'generated_at' => gmdate('c'),
    'assets_total' => count($all),
    'assets_used' => count($used),
    'assets_unused' => count($unused),
    'used' => $used,
    'unused' => $unused,
    'note' => 'This is a best-effort static scan. Some assets are loaded dynamically (PHP concatenation / runtime). Review before deleting.'
];

$out = $reportDir . '/usage_map.json';
file_put_contents($out, json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo "Wrote: {$out}\n";
