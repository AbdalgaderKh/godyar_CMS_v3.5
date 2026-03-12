<?php

function gdy_table_has_column(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1");
        $stmt->execute(['t' => $table, 'c' => $column]);
        $cache[$key] = (bool)$stmt->fetchColumn();
    } catch (\Throwable $e) {
        
        $cache[$key] = false;
    }
    return $cache[$key];
}

function gdy_author_select_fields(PDO $pdo): array
{
    $photoCol = gdy_table_has_column($pdo, 'opinion_authors', 'photo') ? 'photo'
             : (gdy_table_has_column($pdo, 'opinion_authors', 'avatar') ? 'avatar' : null);

    $specCol = gdy_table_has_column($pdo, 'opinion_authors', 'specialization') ? 'specialization' : null;

    $photoExpr = $photoCol ? "oa.`{$photoCol}`" : "NULL";
    $specExpr = $specCol ? "oa.`{$specCol}`"  : "NULL";

    return [$photoExpr, $specExpr];
}

final class SearchService
{
    
    public static function run(PDO $pdo, array $params): array
    {
        if (!is_array($params)) { $params = []; }

        
        
        

        $q = trim((string)($params['q'] ?? ''));
        $mode = (string)($params['mode'] ?? 'any');
        $type = (string)($params['type'] ?? 'all');
        $section = (string)($params['section'] ?? 'all');

        
        $cat = (string)($params['cat'] ?? '');
        $dateKey = (string)($params['date'] ?? 'any'); 
        $match = (string)($params['match'] ?? ''); 

        
        if ($section === 'all' && $cat !== '' && $cat !== 'all') {
            $section = $cat;
        }
        if (($mode === '' || $mode === 'any') && $match !== '') {
            $mode = ($match === 'all') ? 'all' : 'any';
        }

        $allowedModes = ['any', 'all', 'exact'];
        if (!in_array($mode, $allowedModes, true)) {
            $mode = 'any';
        }

        $allowedTypes = ['all', 'news', 'opinion', 'page', 'author'];
        if (!in_array($type, $allowedTypes, true)) {
            $type = 'all';
        }

        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = 10;
        $offset = ($page-1) * $perPage;

        
        
        
        $limitSafe = max(1, min(50, (int)$perPage));
        $offsetSafe = max(0, (int)$offset);

        $baseUrl = rtrim((string)gdy_base_url(), '/');

        
        
        

        
        function gdy_build_text_where(string $query, string $mode, array $columns, string $paramPrefix): array
        {
            $query = trim($query);
            if ($query === '') {
                return ['1=1', []];
            }

            
            $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $terms = array_values(array_filter(array_map('trim', $terms), static fn($t) => $t !== ''));

            
            if ($mode === 'exact' || count($terms) <= 1) {
                $bindings = [];
                $needle = '%' . $query . '%';
                $ors = [];
                foreach ($columns as $i => $col) {
                    $p = ":{$paramPrefix}p{$i}";
                    $ors[] = "{$col} LIKE {$p}";
                    $bindings[$p] = $needle;
                }
                return ['(' .implode(' OR ', $ors) . ')', $bindings];
            }

            
            $join = ($mode === 'all') ? ' AND ' : ' OR ';
            $clauses = [];
            $bindings = [];
            foreach ($terms as $tIdx => $term) {
                $term = trim((string)$term);
                if ($term === '') { continue; }

                
                $ors = [];
                foreach ($columns as $cIdx => $col) {
                    $p = ":{$paramPrefix}{$tIdx}_{$cIdx}";
                    $ors[] = "{$col} LIKE {$p}";
                    $bindings[$p] = '%' . $term . '%';
                }
                if (!empty($ors)) {
                    $clauses[] = '(' .implode(' OR ', $ors) . ')';
                }
            }

            if (empty($clauses)) {
                return ['1=1', []];
            }

            return ['(' .implode($join, $clauses) . ')', $bindings];
        }

        
        function gdy_excerpt(?string $html, int $max = 180): string
        {
            $txt = (string)$html;
            $txt = html_entity_decode($txt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $txt = strip_tags($txt);
            $txt = preg_replace('/\s+/u', ' ', $txt) ?? $txt;
            $txt = trim($txt);

            if (function_exists('mb_strlen') && mb_strlen($txt, 'UTF-8') > $max) {
                $txt = mb_substr($txt, 0, $max, 'UTF-8') . '…';
            } elseif (strlen($txt) > $max) {
                $txt = substr($txt, 0, $max) . '…';
            }
            return $txt;
        }

        
        function gdy_fetch_categories(PDO $pdo): array
        {
            try {
                $stmt = $pdo->query("SELECT slug, name FROM categories WHERE status='active' ORDER BY sort_order ASC, id ASC");
                return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            } catch (\Throwable $e) {
                return [];
            }
        }

        
        
        

        $categories = gdy_fetch_categories($pdo);

        
        $counts = [
            'news' => 0,
            'opinion' => 0, 
            'pages' => 0,
            'authors' => 0,
        ];

        
        $now = date('Y-m-d H:i:s');

        
        
        $dateFrom = '';
        $dateTo = '';
        if ($dateKey !== 'any' && $dateKey !== '') {
            $nowDt = new DateTimeImmutable('now');
            if ($dateKey === '24h') {
                $dateFrom = $nowDt->modify('-1 day')->format('Y-m-d H:i:s');
            } elseif ($dateKey === '7d') {
                $dateFrom = $nowDt->modify('-7 days')->format('Y-m-d H:i:s');
            } elseif ($dateKey === '30d') {
                $dateFrom = $nowDt->modify('-30 days')->format('Y-m-d H:i:s');
            } elseif ($dateKey === 'year') {
                $dateFrom = $nowDt->modify('-365 days')->format('Y-m-d H:i:s');
            }
            $dateTo = $nowDt->format('Y-m-d H:i:s');
        }

        
        $newsWhere = "n.status = 'published' AND n.deleted_at IS NULL AND (n.published_at IS NULL OR n.published_at <= :now)";
        $newsBinds = [':now' => $now];

        
        if ($dateFrom !== '') {
            $newsDateCol = 'n.published_at';
            if (!gdy_table_has_column($pdo, 'news', 'published_at') && gdy_table_has_column($pdo, 'news', 'created_at')) {
                $newsDateCol = 'n.created_at';
            }
            $newsWhere .= " AND {$newsDateCol} >= :news_from";
            $newsBinds[':news_from'] = $dateFrom;
        }

        
        
        list($newsExtra, $newsBindsText) = gdy_build_text_where(
            $q,
            $mode,
            ['n.title', 'n.excerpt', 'n.content', 'n.seo_title', 'n.seo_description', 'n.seo_keywords'],
            'n_'
        );
        $newsBinds = array_merge($newsBinds, $newsBindsText);

        
        if ($section !== 'all' && $section !== '') {
            $newsExtra .= " AND c.slug = :section";
            $newsBinds[':section'] = $section;
        }

        
        
        $searchPages = ($type === 'all' || $type === 'page');
        $searchAuthors = ($type === 'all' || $type === 'author');

        
        [$authorPhotoExpr, $authorSpecExpr] = gdy_author_select_fields($pdo);

        try {

            
            
            $sqlNewsAll = "SELECT COUNT(*) FROM news n LEFT JOIN categories c ON c.id = n.category_id WHERE {$newsExtra} AND {$newsWhere}";
            $stmt = $pdo->prepare($sqlNewsAll);
            foreach ($newsBinds as $k => $v) { $stmt->bindValue($k, $v); }
            $stmt->execute();
            $counts['news'] = (int)$stmt->fetchColumn();

            $sqlOpinion = "SELECT COUNT(*) FROM news n LEFT JOIN categories c ON c.id = n.category_id WHERE {$newsExtra} AND n.opinion_author_id IS NOT NULL AND {$newsWhere}";
            $stmt = $pdo->prepare($sqlOpinion);
            foreach ($newsBinds as $k => $v) { $stmt->bindValue($k, $v); }
            $stmt->execute();
            $counts['opinion'] = (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            
        }

        
        [$pageWhere, $pageBinds] = gdy_build_text_where($q, $mode, ['p.title', 'p.content'], 'p_');
        
        if ($dateFrom !== '') {
            $pageDateCol = 'p.created_at';
            if (!gdy_table_has_column($pdo, 'pages', 'created_at') && gdy_table_has_column($pdo, 'pages', 'published_at')) {
                $pageDateCol = 'p.published_at';
            } elseif (!gdy_table_has_column($pdo, 'pages', 'created_at') && gdy_table_has_column($pdo, 'pages', 'updated_at')) {
                $pageDateCol = 'p.updated_at';
            }
            $pageWhere = "({$pageWhere}) AND {$pageDateCol} >= :page_from";
            $pageBinds[':page_from'] = $dateFrom;
        }

        try {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) AS cnt\n" .
                "FROM pages p\n" .
                "WHERE p.status='published' AND {$pageWhere}"
            );
            foreach ($pageBinds as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->execute();
            $counts['pages'] = (int)($stmt->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            
        }

        
        [$authorWhere, $authorBinds] = gdy_build_text_where($q, $mode, ['oa.name', 'oa.bio'], 'a_');
        try {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) AS cnt\n" .
                "FROM opinion_authors oa\n" .
                "WHERE oa.is_active = 1 AND {$authorWhere}"
            );
            foreach ($authorBinds as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->execute();
            $counts['authors'] = (int)($stmt->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            
        }

        $total = (int)($counts['news'] + $counts['opinion'] + $counts['pages'] + $counts['authors']);

        
        
        

        $results = [];

        
        function gdy_normalize_row(array $row, string $baseUrl): array
        {
            $kind = (string)($row['kind'] ?? 'news');
            $slug = (string)($row['slug'] ?? '');
            $title = (string)($row['title'] ?? '');
            $image = $row['image'] ?? null;
            $createdAt = $row['created_at'] ?? null;
            $categorySlug = $row['category_slug'] ?? null;
            $categoryName = $row['category_name'] ?? null;

            
            if ($kind === 'page') {
                $url = '/ar/page?slug=' .rawurlencode($slug);
            } elseif ($kind === 'author') {
                $url = '/ar/author?slug=' .rawurlencode($slug);
            } else {
                
                $url = '/ar/news?slug=' .rawurlencode($slug);
            }

            return [
                'kind' => $kind,
                'title' => $title,
                'url' => $url,
                'image' => $image ? (string)$image : null,
                'excerpt' => gdy_excerpt((string)($row['excerpt'] ?? '')),
                'created_at' => $createdAt ? (string)$createdAt : null,
                'category_slug' => $categorySlug ? (string)$categorySlug : null,
                'category_name' => $categoryName ? (string)$categoryName : null,
            ];
        }

        try {
            if ($type === 'page') {
                $sql =                     "SELECT 'page' AS kind, p.title, p.slug, NULL AS image, p.content AS excerpt, p.updated_at AS created_at, NULL AS category_slug, NULL AS category_name\n" .
                    "FROM pages p\n" .
                    "WHERE p.status='published' AND {$pageWhere}\n" .
                    "ORDER BY p.updated_at DESC\n" .
                    "LIMIT {$limitSafe} OFFSET {$offsetSafe}";

                $stmt = $pdo->prepare($sql);
                foreach ($pageBinds as $k => $v) {
                    $stmt->bindValue($k, $v, PDO::PARAM_STR);
                }
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $row) {
                    $results[] = gdy_normalize_row($row, $baseUrl);
                }
            } elseif ($type === 'author') {
                $sql =                     "SELECT 'author' AS kind, oa.name AS title, oa.slug, {$authorPhotoExpr} AS image, oa.bio AS excerpt, oa.created_at, NULL AS category_slug, NULL AS category_name\n" .
                    "FROM opinion_authors oa\n" .
                    "WHERE oa.is_active = 1 AND {$authorWhere}\n" .
                    "ORDER BY oa.created_at DESC\n" .
                    "LIMIT {$limitSafe} OFFSET {$offsetSafe}";

                $stmt = $pdo->prepare($sql);
                foreach ($authorBinds as $k => $v) {
                    $stmt->bindValue($k, $v, PDO::PARAM_STR);
                }
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $row) {
                    $results[] = gdy_normalize_row($row, $baseUrl);
                }
            } elseif ($type === 'news' || $type === 'opinion') {
                $tBinds = $newsBinds;
                $extra = $newsExtra;
                if ($type === 'opinion') {
                    $extra .= ' AND n.opinion_author_id IS NOT NULL';
                }

                $sql =                     "SELECT CASE WHEN n.opinion_author_id IS NOT NULL THEN 'opinion' ELSE 'news' END AS kind, n.title, n.slug, n.image, n.excerpt, n.created_at, c.slug AS category_slug, c.name AS category_name\n" .
                    "FROM news n\n" .
                    "LEFT JOIN categories c ON c.id = n.category_id\n" .
                    "WHERE {$extra} AND {$newsWhere}\n" .
                    "ORDER BY n.created_at DESC\n" .
                    "LIMIT {$limitSafe} OFFSET {$offsetSafe}";

                $stmt = $pdo->prepare($sql);
                foreach ($tBinds as $k => $v) {
                    $stmt->bindValue($k, $v, PDO::PARAM_STR);
                }
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $row) {
                    $results[] = gdy_normalize_row($row, $baseUrl);
                }
            } else {
                
                $unionSql =                     "(\n" .
                    "  SELECT CASE WHEN n.opinion_author_id IS NOT NULL THEN 'opinion' ELSE 'news' END AS kind, n.title, n.slug, n.image, n.excerpt, n.created_at, c.slug AS category_slug, c.name AS category_name\n" .
                    "  FROM news n\n" .
                    "  LEFT JOIN categories c ON c.id = n.category_id\n" .
                    "  WHERE {$newsExtra} AND {$newsWhere}\n" .
                    ")\n" .
                    "UNION ALL\n" .
                    "(\n" .
                    "  SELECT 'page' AS kind, p.title, p.slug, NULL AS image, p.content AS excerpt, p.updated_at AS created_at, NULL AS category_slug, NULL AS category_name\n" .
                    "  FROM pages p\n" .
                    "  WHERE p.status='published' AND {$pageWhere}\n" .
                    ")\n" .
                    "UNION ALL\n" .
                    "(\n" .
                    "  SELECT 'author' AS kind, oa.name AS title, oa.slug, {$authorPhotoExpr} AS image, oa.bio AS excerpt, oa.created_at AS created_at, NULL AS category_slug, NULL AS category_name\n" .
                    "  FROM opinion_authors oa\n" .
                    "  WHERE oa.is_active = 1 AND {$authorWhere}\n" .
                    ")";

                $sql = "SELECT * FROM (\n{$unionSql}\n) u ORDER BY u.created_at DESC LIMIT {$limitSafe} OFFSET {$offsetSafe}";

                $stmt = $pdo->prepare($sql);
                
                foreach ([$newsBinds, $pageBinds, $authorBinds] as $binds) {
                    foreach ($binds as $k => $v) {
                        $stmt->bindValue($k, $v, PDO::PARAM_STR);
                    }
                }
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $row) {
                    $results[] = gdy_normalize_row($row, $baseUrl);
                }
            }
        } catch (\Throwable $e) {
            
            
            error_log('[GodyarSearch] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $results = [];
        }

        
        if ($type === 'page') {
            $typeTotal = $counts['pages'];
        } elseif ($type === 'author') {
            $typeTotal = $counts['authors'];
        } elseif ($type === 'opinion') {
            $typeTotal = $counts['opinion'];
        } elseif ($type === 'news') {
            
            $typeTotal = $counts['news'];
        } else {
            $typeTotal = $total;
        }

        $pages = max(1, (int)ceil(($typeTotal ?: 0) / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }

        
        
        

        return [
            'q' => $q,
            'mode' => $mode,
            'type' => $type,
            'section' => $section,
            'dateKey' => $dateKey,
            'page' => $page,
            'perPage' => $perPage,
            'results' => $results,
            'counts' => $counts,
            'total' => $total,
            'pages' => $pages,
            'categories' => $categories,
            'baseUrl' => $baseUrl,
        ];
    }
}
