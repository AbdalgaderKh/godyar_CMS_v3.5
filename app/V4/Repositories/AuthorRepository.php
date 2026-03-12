<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class AuthorRepository
{
    private function storageFile(): string
    {
        return godyar_v4_storage_path('v4/authors.json');
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

    public function findBySlug(string $slug, string $locale = 'ar'): ?array
    {
        foreach ($this->readStorage() as $row) {
            if (($row['slug'] ?? '') === $slug && empty($row['_deleted'])) {
                return $this->normalize($row + ['source' => 'storage']);
            }
        }

        $pdo = godyar_v4_db();
        if ($pdo) {
            foreach ([
                "SELECT id, slug, name, bio, avatar, updated_at FROM authors WHERE slug = :slug LIMIT 1",
                "SELECT id, slug, name, bio, avatar, updated_at FROM opinion_authors WHERE slug = :slug LIMIT 1",
                "SELECT id, slug, name, bio, avatar, updated_at FROM users WHERE slug = :slug LIMIT 1",
            ] as $sql) {
                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['slug' => $slug]);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($row) {
                        return $this->normalize($row + ['source' => 'db']);
                    }
                } catch (\Throwable) {
                }
            }
        }

        return [
            'id' => 0,
            'slug' => $slug,
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'bio' => 'ملف كاتب انتقالي ضمن بنية v4.',
            'avatar' => null,
            'updated_at' => date('c'),
            'source' => 'virtual',
        ];
    }

    public function articlesByAuthor(string $slug, string $locale = 'ar', int $limit = 12): array
    {
        $pdo = godyar_v4_db();
        if ($pdo) {
            $queries = [
                "SELECT n.id, n.title, n.slug, COALESCE(n.excerpt, n.summary, '') AS excerpt, COALESCE(n.published_at, n.created_at, n.updated_at) AS published_at
                 FROM news n INNER JOIN authors a ON a.id = n.author_id
                 WHERE a.slug = :slug
                 ORDER BY COALESCE(n.published_at, n.created_at, n.updated_at) DESC
                 LIMIT " . max(1, $limit),
                "SELECT n.id, n.title, n.slug, COALESCE(n.excerpt, n.summary, '') AS excerpt, COALESCE(n.published_at, n.created_at, n.updated_at) AS published_at
                 FROM news n
                 WHERE COALESCE(n.author_slug,'') = :slug OR COALESCE(n.author,'') = :slug
                 ORDER BY COALESCE(n.published_at, n.created_at, n.updated_at) DESC
                 LIMIT " . max(1, $limit),
            ];
            foreach ($queries as $sql) {
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
            $items[$row['slug']] = $this->normalize($row + ['source' => 'storage']);
        }

        $pdo = godyar_v4_db();
        if ($pdo) {
            foreach ([
                "SELECT id, slug, name, bio, avatar, updated_at FROM authors ORDER BY COALESCE(updated_at, id) DESC LIMIT {$limit}",
                "SELECT id, slug, name, bio, avatar, updated_at FROM opinion_authors ORDER BY COALESCE(updated_at, id) DESC LIMIT {$limit}",
                "SELECT id, slug, name, bio, avatar, updated_at FROM users ORDER BY COALESCE(updated_at, id) DESC LIMIT {$limit}",
            ] as $sql) {
                try {
                    $rows = $pdo->query($sql)?->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    if ($rows) {
                        foreach ($rows as $row) {
                            $slug = (string)($row['slug'] ?? '');
                            if ($slug !== '' && !isset($items[$slug])) {
                                $items[$slug] = $this->normalize($row + ['source' => 'db']);
                            }
                        }
                        break;
                    }
                } catch (\Throwable) {
                }
            }
        }

        if (!$items) {
            $items['editorial-team'] = $this->normalize([
                'id' => 1,
                'slug' => 'editorial-team',
                'name' => 'Editorial Team',
                'bio' => 'فريق تحريري افتراضي لحين ربط البيانات الفعلية.',
                'avatar' => null,
                'updated_at' => date('c'),
                'source' => 'fallback',
            ]);
        }
        return array_slice(array_values($items), 0, $limit);
    }

    public function create(array $data): array
    {
        $row = $this->normalize([
            'id' => time(),
            'slug' => godyar_v4_slugify((string)($data['slug'] ?? $data['name'] ?? 'author')),
            'name' => trim((string)($data['name'] ?? '')),
            'bio' => trim((string)($data['bio'] ?? '')),
            'avatar' => trim((string)($data['avatar'] ?? '')),
            'updated_at' => date('c'),
            'source' => 'storage',
        ]);
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
            $row['bio'] = trim((string)($data['bio'] ?? $row['bio'] ?? ''));
            $row['avatar'] = trim((string)($data['avatar'] ?? $row['avatar'] ?? ''));
            $row['updated_at'] = date('c');
            $updated = $this->normalize($row + ['source' => 'storage']);
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

    private function normalize(array $row): array
    {
        $row['bio'] = $row['bio'] ?? '';
        $row['updated_at'] = $row['updated_at'] ?? date('c');
        $row['articles_count'] = $row['articles_count'] ?? 0;
        $row['source'] = $row['source'] ?? 'db';
        return $row;
    }
}
