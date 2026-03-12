<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class TagRepository
{
    private function storageFile(): string
    {
        return godyar_v4_storage_path('v4/tags.json');
    }

    private function readStorage(): array
    {
        $file = $this->storageFile();
        if (!is_file($file)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($file), true);
        return is_array($rows) ? $rows : [];
    }

    private function writeStorage(array $rows): void
    {
        $file = $this->storageFile();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($file, json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function forNews(int $newsId): array
    {
        $pdo = godyar_v4_db();
        if ($pdo && $newsId > 0) {
            foreach ([
                "SELECT t.id, t.name, t.slug FROM tags t INNER JOIN news_tags nt ON nt.tag_id = t.id WHERE nt.news_id = :news_id ORDER BY t.name ASC",
                "SELECT t.id, t.name, t.slug FROM tags t INNER JOIN post_tags nt ON nt.tag_id = t.id WHERE nt.post_id = :news_id ORDER BY t.name ASC",
            ] as $sql) {
                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['news_id' => $newsId]);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    if ($rows) {
                        return $rows;
                    }
                } catch (\Throwable) {
                }
            }
            try {
                $stmt = $pdo->prepare("SELECT tags FROM news WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $newsId]);
                $csv = (string)($stmt->fetchColumn() ?: '');
                if ($csv !== '') {
                    return $this->parseCsvTags($csv);
                }
            } catch (\Throwable) {
            }
        }
        return [];
    }

    public function findBySlug(string $slug, string $locale = 'ar'): ?array
    {
        foreach ($this->readStorage() as $row) {
            if (($row['slug'] ?? '') === $slug && empty($row['_deleted'])) {
                return $row + ['source' => 'storage'];
            }
        }
        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT id, name, slug, updated_at FROM tags WHERE slug = :slug LIMIT 1");
                $stmt->execute(['slug' => $slug]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    return $row + ['source' => 'db'];
                }
            } catch (\Throwable) {
            }
        }
        return ['id' => 0, 'name' => ucfirst(str_replace('-', ' ', $slug)), 'slug' => $slug, 'updated_at' => date('c'), 'source' => 'virtual'];
    }

    public function articlesByTag(string $slug, string $locale = 'ar', int $limit = 12): array
    {
        $pdo = godyar_v4_db();
        if ($pdo) {
            foreach ([
                "SELECT n.id, n.title, n.slug, COALESCE(n.excerpt, n.summary, '') AS excerpt, COALESCE(n.published_at, n.created_at, n.updated_at) AS published_at
                 FROM news n INNER JOIN news_tags nt ON nt.news_id = n.id INNER JOIN tags t ON t.id = nt.tag_id
                 WHERE t.slug = :slug
                 ORDER BY COALESCE(n.published_at, n.created_at, n.updated_at) DESC
                 LIMIT " . max(1, $limit),
                "SELECT n.id, n.title, n.slug, COALESCE(n.excerpt, n.summary, '') AS excerpt, COALESCE(n.published_at, n.created_at, n.updated_at) AS published_at
                 FROM news n
                 WHERE FIND_IN_SET(:slug, REPLACE(COALESCE(n.tags,''), ' ', '')) > 0
                 ORDER BY COALESCE(n.published_at, n.created_at, n.updated_at) DESC
                 LIMIT " . max(1, $limit),
            ] as $sql) {
                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['slug' => $slug]);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    if ($rows) {
                        return $rows;
                    }
                } catch (\Throwable) {
                }
            }
        }
        return [];
    }

    public function all(int $limit = 100): array
    {
        $limit = max(1, $limit);
        $items = [];
        foreach ($this->readStorage() as $row) {
            if (!empty($row['_deleted'])) {
                continue;
            }
            $items[$row['slug']] = $row + ['source' => 'storage'];
        }
        $pdo = godyar_v4_db();
        if ($pdo) {
            foreach ([
                "SELECT id, name, slug, updated_at FROM tags ORDER BY COALESCE(updated_at, id) DESC LIMIT {$limit}",
                "SELECT id, name, slug, updated_at FROM topic_tags ORDER BY COALESCE(updated_at, id) DESC LIMIT {$limit}",
            ] as $sql) {
                try {
                    $rows = $pdo->query($sql)?->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    if ($rows) {
                        foreach ($rows as $row) {
                            $slug = (string)($row['slug'] ?? '');
                            if ($slug !== '' && !isset($items[$slug])) {
                                $items[$slug] = $row + ['source' => 'db'];
                            }
                        }
                        break;
                    }
                } catch (\Throwable) {
                }
            }
        }
        if (!$items) {
            $items['general'] = ['id' => 1, 'name' => 'General', 'slug' => 'general', 'updated_at' => date('c'), 'source' => 'fallback'];
        }
        return array_slice(array_values($items), 0, $limit);
    }

    public function create(array $data): array
    {
        $row = [
            'id' => time(),
            'name' => trim((string)($data['name'] ?? '')),
            'slug' => godyar_v4_slugify((string)($data['slug'] ?? $data['name'] ?? 'tag')),
            'updated_at' => date('c'),
            'source' => 'storage',
        ];
        $rows = array_values(array_filter($this->readStorage(), fn(array $item): bool => (string)($item['slug'] ?? '') !== $row['slug']));
        array_unshift($rows, $row);
        $this->writeStorage($rows);
        return $row;
    }

    public function updateBySlug(string $slug, array $data): ?array
    {
        $rows = $this->readStorage();
        $updated = null;
        foreach ($rows as &$row) {
            if ((string)($row['slug'] ?? '') !== $slug) {
                continue;
            }
            $row['name'] = trim((string)($data['name'] ?? $row['name'] ?? ''));
            $row['updated_at'] = date('c');
            $updated = $row + ['source' => 'storage'];
            break;
        }
        unset($row);
        if (!$updated) {
            return null;
        }
        $this->writeStorage($rows);
        return $updated;
    }

    public function deleteBySlug(string $slug): bool
    {
        $rows = $this->readStorage();
        $new = [];
        $deleted = false;
        foreach ($rows as $row) {
            if ((string)($row['slug'] ?? '') === $slug) {
                $deleted = true;
                continue;
            }
            $new[] = $row;
        }
        if ($deleted) {
            $this->writeStorage($new);
        }
        return $deleted;
    }

    private function parseCsvTags(string $csv): array
    {
        $parts = array_filter(array_map('trim', explode(',', $csv)));
        $rows = [];
        foreach ($parts as $i => $tag) {
            $rows[] = [
                'id' => $i + 1,
                'name' => $tag,
                'slug' => strtolower(trim((string)preg_replace('/[^a-zA-Z0-9\-_]+/u', '-', $tag), '-')) ?: ('tag-' . ($i + 1)),
            ];
        }
        return $rows;
    }
}
