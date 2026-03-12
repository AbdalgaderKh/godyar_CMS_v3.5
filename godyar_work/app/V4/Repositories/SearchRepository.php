<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class SearchRepository
{
    public function search(string $term, string $locale = 'ar', int $limit = 20): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $results = [];
        $pdo = godyar_v4_db();
        if ($pdo) {
            $like = '%' . $term . '%';
            try {
                $sql = "SELECT id, title, slug, excerpt, published_at, updated_at
                        FROM news
                        WHERE deleted_at IS NULL
                          AND (title LIKE :term OR excerpt LIKE :term OR content LIKE :term)
                        ORDER BY COALESCE(published_at, created_at, updated_at) DESC
                        LIMIT " . max(1, $limit);
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['term' => $like]);
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                    $results[] = [
                        'type' => 'news',
                        'title' => (string)($row['title'] ?? ''),
                        'slug' => (string)($row['slug'] ?? ''),
                        'url' => godyar_v4_url($locale, 'news/' . ($row['slug'] ?? '')),
                        'excerpt' => (string)($row['excerpt'] ?? ''),
                        'updated_at' => (string)($row['updated_at'] ?? $row['published_at'] ?? date('c')),
                    ];
                }
            } catch (\Throwable) {
            }

            try {
                $sql = "SELECT id, title, slug, content, updated_at
                        FROM pages
                        WHERE title LIKE :term OR slug LIKE :term OR content LIKE :term
                        ORDER BY id DESC
                        LIMIT " . max(1, (int) ceil($limit / 2));
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['term' => $like]);
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                    $results[] = [
                        'type' => 'page',
                        'title' => (string)($row['title'] ?? ''),
                        'slug' => (string)($row['slug'] ?? ''),
                        'url' => godyar_v4_url($locale, 'page/' . ($row['slug'] ?? '')),
                        'excerpt' => mb_substr(trim(strip_tags((string)($row['content'] ?? ''))), 0, 180),
                        'updated_at' => (string)($row['updated_at'] ?? date('c')),
                    ];
                }
            } catch (\Throwable) {
            }

            try {
                $sql = "SELECT id, name, slug, description, updated_at
                        FROM categories
                        WHERE name LIKE :term OR slug LIKE :term OR description LIKE :term
                        ORDER BY id DESC
                        LIMIT 10";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['term' => $like]);
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [] as $row) {
                    $results[] = [
                        'type' => 'category',
                        'title' => (string)($row['name'] ?? ''),
                        'slug' => (string)($row['slug'] ?? ''),
                        'url' => godyar_v4_url($locale, 'category/' . ($row['slug'] ?? '')),
                        'excerpt' => (string)($row['description'] ?? ''),
                        'updated_at' => (string)($row['updated_at'] ?? date('c')),
                    ];
                }
            } catch (\Throwable) {
            }
        }

        if (!$results) {
            $newsRepo = new NewsRepository();
            foreach ($newsRepo->latest($locale, min($limit, 8)) as $row) {
                $title = (string)($row['title'] ?? '');
                $excerpt = (string)($row['excerpt'] ?? '');
                if (stripos($title, $term) === false && stripos($excerpt, $term) === false) {
                    continue;
                }
                $results[] = [
                    'type' => 'news',
                    'title' => $title,
                    'slug' => (string)($row['slug'] ?? ''),
                    'url' => godyar_v4_url($locale, 'news/' . ($row['slug'] ?? '')),
                    'excerpt' => $excerpt,
                    'updated_at' => (string)($row['updated_at'] ?? $row['published_at'] ?? date('c')),
                ];
            }
        }

        usort($results, static function (array $a, array $b): int {
            return strcmp((string)($b['updated_at'] ?? ''), (string)($a['updated_at'] ?? ''));
        });

        return array_slice($results, 0, $limit);
    }

    public function suggest(string $term, string $locale = 'ar', int $limit = 6): array
    {
        $items = $this->search($term, $locale, $limit);
        return array_values(array_map(static fn(array $item) => [
            'title' => (string)($item['title'] ?? ''),
            'url' => (string)($item['url'] ?? ''),
            'type' => (string)($item['type'] ?? 'item'),
        ], $items));
    }
}
