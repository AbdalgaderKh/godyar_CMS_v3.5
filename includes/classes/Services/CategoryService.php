<?php
namespace Godyar\Services;

use PDO;
use Throwable;

final class CategoryService
{
    public function __construct(private PDO $pdo) {}

    
    private static array $colCache = [];
    
    private static array $tableCache = [];

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . ':' . $column;
        if (array_key_exists($key, self::$colCache)) {
            return (bool) self::$colCache[$key];
        }

        $exists = false;
        try {
            $schemaExpr = function_exists('gdy_db_schema_expr') ? gdy_db_schema_expr($this->pdo) : 'DATABASE()';
            $stmt = $this->pdo->prepare(
                "SELECT 1
                 FROM information_schema .columns
                 WHERE table_schema = {$schemaExpr}
                   AND table_name = :t
                   AND column_name = :c
                 LIMIT 1"
            );
            $stmt->execute([':t' => $table, ':c' => $column]);
            $exists = (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            $exists = false;
        }

        self::$colCache[$key] = $exists;
        return $exists;
    }

    private function hasTable(string $table): bool
    {
        $table = trim($table);
        if ($table === '') return false;

        if (array_key_exists($table, self::$tableCache)) {
            return (bool) self::$tableCache[$table];
        }

        $exists = false;
        try {
            $schemaExpr = function_exists('gdy_db_schema_expr') ? gdy_db_schema_expr($this->pdo) : 'DATABASE()';
            $stmt = $this->pdo->prepare(
                "SELECT 1
                 FROM information_schema .tables
                 WHERE table_schema = {$schemaExpr}
                   AND table_name = :t
                 LIMIT 1"
            );
            $stmt->execute([':t' => $table]);
            $exists = (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            $exists = false;
        }

        self::$tableCache[$table] = $exists;
        return $exists;
    }

    private function slugColumn(): ?string
    {
        if ($this->hasColumn('categories', 'slug')) return 'slug';
        foreach (['category_slug', 'slug_name', 'permalink', 'url_slug'] as $alt) {
            if ($this->hasColumn('categories', $alt)) return $alt;
        }
        return null;
    }

    private function nameColumn(): string
    {
        if ($this->hasColumn('categories', 'name')) return 'name';
        foreach (['category_name', 'cat_name', 'title'] as $alt) {
            if ($this->hasColumn('categories', $alt)) return $alt;
        }
        return 'name';
    }

    private function publishedWhere(string $alias = 'n'): string
    {
        $clauses = [];
        $prefix = $alias !== '' ? (rtrim($alias, '.') . '.') : '';

        if ($this->hasColumn('news', 'status')) {
            $col = "{$prefix}status";
            $clauses[] = "({$col} = 'published' OR {$col} = 'publish' OR {$col} = 'active' OR {$col} = 'approved' OR {$col} = 1 OR {$col} = '1')";
        }
        foreach (['is_published', 'published', 'is_active', 'active'] as $flag) {
            if ($this->hasColumn('news', $flag)) {
                $col = "{$prefix}`{$flag}`";
                $clauses[] = "({$col} = 1 OR {$col} = '1' OR {$col} = 'yes' OR {$col} = 'true')";
            }
        }

        $where = $clauses ? ('(' .implode(' OR ', $clauses) . ')') : '1=1';

        if ($this->hasColumn('news', 'publish_at')) {
            $col = "{$prefix}publish_at";
            $where .= " AND ({$col} IS NULL OR {$col} <= NOW())";
        }
        if ($this->hasColumn('news', 'unpublish_at')) {
            $col = "{$prefix}unpublish_at";
            $where .= " AND ({$col} IS NULL OR {$col} > NOW())";
        }
        if ($this->hasColumn('news', 'deleted_at')) {
            $col = "{$prefix}deleted_at";
            $where .= " AND ({$col} IS NULL)";
        }

        return $where;
    }

    public function slugById(int $id): ?string
    {
        $slugCol = $this->slugColumn();
        if ($slugCol === null || $id <= 0) return null;

        try {
            $st = $this->pdo->prepare('SELECT `' . $slugCol . '` FROM categories WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            $slug = trim((string)($st->fetchColumn() ?: ''));
            return $slug !== '' ? $slug : null;
        } catch (\Throwable $e) {
            error_log('[CategoryService] slugById error: ' . $e->getMessage());
            return null;
        }
    }

    public function idBySlug(string $slug): ?int
    {
        $slug = trim($slug);
        if ($slug === '') return null;
        if (ctype_digit($slug)) return (int) $slug;

        if ($this->hasTable('category_slug_map')) {
            try {
                $st = $this->pdo->prepare('SELECT category_id FROM category_slug_map WHERE slug = :s LIMIT 1');
                $st->execute([':s' => $slug]);
                $id = (int)($st->fetchColumn() ?: 0);
                if ($id > 0) return $id;
            } catch (Throwable) {
                
            }
        }

        $slugCol = $this->slugColumn();
        if ($slugCol === null) return null;

        try {
            $st = $this->pdo->prepare('SELECT id FROM categories WHERE `' . $slugCol . '` = :s LIMIT 1');
            $st->execute([':s' => $slug]);
            $id = (int)($st->fetchColumn() ?: 0);
            return $id > 0 ? $id : null;
        } catch (Throwable) {
            return null;
        }
    }

    
    public function findBySlugOrId(string $param): ?array
    {
        $param = trim($param);
        if ($param === '') return null;

        $isNumeric = ctype_digit($param);
        $id = $isNumeric ? (int)$param : 0;

        $nameCol = $this->nameColumn();
        $slugCol = $this->slugColumn();

        $cols = ["id", "`{$nameCol}` AS name"];
        $cols[] = $slugCol ? "`{$slugCol}` AS slug" : "'' AS slug";
        if ($this->hasColumn('categories', 'description')) $cols[] = 'description';
        if ($this->hasColumn('categories', 'parent_id')) $cols[] = 'parent_id';
        if ($this->hasColumn('categories', 'is_active')) $cols[] = 'is_active';
        if ($this->hasColumn('categories', 'meta_title')) $cols[] = 'meta_title';
        if ($this->hasColumn('categories', 'meta_description')) $cols[] = 'meta_description';
        if ($this->hasColumn('categories', 'is_members_only')) {
            $cols[] = 'is_members_only';
        } else {
            $cols[] = '0 AS is_members_only';
        }

        $where = $isNumeric ? 'id = :id' : ($slugCol ? ('`' . $slugCol . '` = :s') : '1=0');
        $sql = 'SELECT ' .implode(', ', $cols) . ' FROM categories WHERE ' . $where . ' LIMIT 1';

        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($isNumeric ? [':id' => $id] : [':s' => $param]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            return $row;
        } catch (\Throwable $e) {
            error_log('[CategoryService] findBySlugOrId error: ' . $e->getMessage());
            return null;
        }
    }

    
    public function findById(int $id): ?array
    {
        if ($id <= 0) return null;
        return $this->findBySlugOrId((string)$id);
    }

    
    public function findBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') return null;
        return $this->findBySlugOrId($slug);
    }

    
    public function all(int $limit = 500): array
    {
        $limit = max(1, min(2000, $limit));
        $nameCol = $this->nameColumn();
        $slugCol = $this->slugColumn();

        $cols = ["id", "`{$nameCol}` AS name"];
        $cols[] = $slugCol ? "`{$slugCol}` AS slug" : "'' AS slug";

        $sql = 'SELECT ' .implode(', ', $cols) . ' FROM categories ORDER BY `' . $nameCol . '` ASC LIMIT :lim';

        try {
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('[CategoryService] all error: ' . $e->getMessage());
            return [];
        }
    }

    
    public function headerCategories(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));

        $nameCol = $this->nameColumn();
        $slugCol = $this->slugColumn();

        $cols = ["id", "`{$nameCol}` AS name"];
        $cols[] = $slugCol ? "`{$slugCol}` AS slug" : "'' AS slug";

        if ($this->hasColumn('categories', 'parent_id')) $cols[] = 'parent_id';
        if ($this->hasColumn('categories', 'sort_order')) $cols[] = 'sort_order';

        $where = [];
        if ($this->hasColumn('categories', 'is_active')) $where[] = 'is_active = 1';

        $order = [];
        if ($this->hasColumn('categories', 'sort_order')) $order[] = 'sort_order ASC';
        $order[] = "`{$nameCol}` ASC";
        $order[] = 'id ASC';

        $sql = 'SELECT ' .implode(', ', $cols) . ' FROM categories'
            . ($where ? (' WHERE ' .implode(' AND ', $where)) : '')
            . ' ORDER BY ' .implode(', ', $order)
            . ' LIMIT :lim';

        try {
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('[CategoryService] headerCategories error: ' . $e->getMessage());
            return [];
        }
    }

    
    public function subcategories(int $parentId, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        if ($parentId <= 0) return [];
        if (!$this->hasColumn('categories', 'parent_id')) return [];

        $nameCol = $this->nameColumn();
        $slugCol = $this->slugColumn();

        $cols = ["id", "`{$nameCol}` AS name", 'parent_id'];
        $cols[] = $slugCol ? "`{$slugCol}` AS slug" : "'' AS slug";

        $sql = 'SELECT ' .implode(', ', $cols) . ' FROM categories WHERE parent_id = :pid ORDER BY `' . $nameCol . '` ASC LIMIT :lim';

        try {
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':pid', $parentId, PDO::PARAM_INT);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    
    public function siblingCategories(?int $parentIdOrNull, int $excludeCategoryId, int $limit = 20): array
    {
        $limit = max(1, min(200, (int)$limit));
        $excludeCategoryId = (int)$excludeCategoryId;
        if (!$this->hasTable('categories')) return [];
        if (!$this->hasColumn('categories', 'parent_id')) return [];

        $nameCol = $this->nameColumn();
        $slugCol = $this->slugColumn();
        $cols = ["id", "`{$nameCol}` AS name", "parent_id"];
        $cols[] = $slugCol ? "`{$slugCol}` AS slug" : "'' AS slug";

        $where = '';
        $params = [':exclude' => $excludeCategoryId, ':lim' => $limit];

        if ($parentIdOrNull === null) {
            
            $where = '(parent_id IS NULL OR parent_id = 0)';
        } else {
            $pid = (int)$parentIdOrNull;
            if ($pid <= 0) {
                $where = '(parent_id IS NULL OR parent_id = 0)';
            } else {
                $where = 'parent_id = :pid';
                $params[':pid'] = $pid;
            }
        }

        
        if ($excludeCategoryId > 0) {
            $where = '(' . $where . ') AND id <> :exclude';
        }

        $sql = 'SELECT ' .implode(', ', $cols)
            . ' FROM categories'
            . ' WHERE ' . $where
            . ' ORDER BY `' . $nameCol . '` ASC'
            . ' LIMIT :lim';

        try {
            $st = $this->pdo->prepare($sql);
            foreach ($params as $k => $v) {
                if ($k === ':lim' || $k === ':pid' || $k === ':exclude') {
                    $st->bindValue($k, (int)$v, PDO::PARAM_INT);
                } else {
                    $st->bindValue($k, $v);
                }
            }
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('[CategoryService] siblingCategories error: ' . $e->getMessage());
            return [];
        }
    }

    
    public function listNews(int $categoryId, int $page = 1, int $perPage = 12, string $sort = 'latest', string $period = 'all'): array
    {
        return $this->listPublishedNews($categoryId, $page, $perPage, $sort, $period);
    }

    
    
    public function listPublishedNews(int $categoryId, int $page = 1, int $perPage = 12, string $sort = 'latest', string $period = 'all'): array
    {
        $categoryId = (int)$categoryId;
        $page = max(1, (int)$page);
        $perPage = max(1, min(60, (int)$perPage));

        if ($categoryId <= 0 || !$this->hasTable('news')) {
            return ['items' => [], 'total' => 0, 'total_pages' => 1, 'page' => $page, 'per_page' => $perPage];
        }

        $offset = ($page - 1) * $perPage;
        $slug = $this->slugById($categoryId);

        $dateCol = $this->hasColumn('news', 'publish_at') ? 'publish_at'
            : ($this->hasColumn('news', 'published_at') ? 'published_at'
                : ($this->hasColumn('news', 'created_at') ? 'created_at' : 'id'));

        $imgCol = $this->hasColumn('news', 'featured_image') ? 'featured_image'
            : ($this->hasColumn('news', 'image_path') ? 'image_path'
                : ($this->hasColumn('news', 'image') ? 'image' : null));

        $excerptCol = $this->hasColumn('news', 'excerpt') ? 'excerpt'
            : ($this->hasColumn('news', 'summary') ? 'summary' : null);

        $viewCol = $this->hasColumn('news', 'views') ? 'views' : ($this->hasColumn('news', 'view_count') ? 'view_count' : null);
        $order = "n.`{$dateCol}` DESC, n.id DESC";
        if (in_array($sort, ['popular', 'most_read', 'views'], true) && $viewCol) {
            $order = "n.`{$viewCol}` DESC, n.`{$dateCol}` DESC, n.id DESC";
        } elseif ($sort === 'oldest') {
            $order = "n.`{$dateCol}` ASC, n.id ASC";
        }

        $joinModes = [];
        if ($this->hasColumn('news', 'category_id')) $joinModes[] = 'category_id';
        if ($slug !== null && $this->hasColumn('news', 'category_slug')) $joinModes[] = 'category_slug';
        if ($slug !== null && $this->hasColumn('news', 'category')) $joinModes[] = 'category';
        if (!$joinModes) $joinModes[] = 'none';

        $select = ['n.*'];
        if ($dateCol !== 'id') $select[] = "n.`{$dateCol}` AS publish_at";
        if ($imgCol) $select[] = "n.`{$imgCol}` AS featured_image";
        if ($excerptCol) $select[] = "n.`{$excerptCol}` AS excerpt";

        foreach ($joinModes as $mode) {
            $params = [];
            if ($mode === 'category_id') {
                $where = 'n.category_id = :cid';
                $params[':cid'] = $categoryId;
            } elseif ($mode === 'category_slug') {
                $where = 'n.category_slug = :slug';
                $params[':slug'] = (string)$slug;
            } elseif ($mode === 'category') {
                $where = 'n.category = :slug';
                $params[':slug'] = (string)$slug;
            } else {
                $where = '1=0';
            }

            $activeWhere = $where . ' AND ' . $this->publishedWhere('n');
            if ($period !== 'all' && $dateCol !== 'id') {
                $days = match ($period) {
                    'today', 'day' => 1,
                    'week' => 7,
                    'month' => 30,
                    'year' => 365,
                    default => 0,
                };
                if ($days > 0) {
                    $activeWhere .= " AND n.`{$dateCol}` >= (NOW()-INTERVAL {$days} DAY)";
                }
            }

            $total = 0;
            try {
                $st = $this->pdo->prepare("SELECT COUNT(*) FROM news n WHERE {$activeWhere}");
                $st->execute($params);
                $total = (int)($st->fetchColumn() ?: 0);
            } catch (\Throwable $e) {
                error_log('[CategoryService] strict count error: ' . $e->getMessage());
            }

            if ($total === 0) {
                $fallbackWhere = $where;
                if ($this->hasColumn('news', 'deleted_at')) {
                    $fallbackWhere .= ' AND (n.deleted_at IS NULL OR n.deleted_at = "" OR n.deleted_at = "0000-00-00 00:00:00")';
                }
                try {
                    $st = $this->pdo->prepare("SELECT COUNT(*) FROM news n WHERE {$fallbackWhere}");
                    $st->execute($params);
                    $total = (int)($st->fetchColumn() ?: 0);
                    if ($total > 0) {
                        $activeWhere = $fallbackWhere;
                    }
                } catch (\Throwable $e) {
                    error_log('[CategoryService] fallback count error: ' . $e->getMessage());
                }
            }

            if ($total > 0) {
                $totalPages = max(1, (int)ceil($total / $perPage));
                $items = [];
                try {
                    $sql = 'SELECT ' . implode(', ', $select) . " FROM news n WHERE {$activeWhere} ORDER BY {$order} LIMIT :lim OFFSET :off";
                    $st = $this->pdo->prepare($sql);
                    foreach ($params as $k => $v) {
                        $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
                    }
                    $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
                    $st->bindValue(':off', $offset, PDO::PARAM_INT);
                    $st->execute();
                    $items = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (\Throwable $e) {
                    error_log('[CategoryService] list error: ' . $e->getMessage());
                }

                return [
                    'items' => $items,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'page' => $page,
                    'per_page' => $perPage,
                ];
            }
        }

        return ['items' => [], 'total' => 0, 'total_pages' => 1, 'page' => $page, 'per_page' => $perPage];
    }

    public function version(): string
    {
        return 'CategoryService v3 2026-01-17';
    }
}
