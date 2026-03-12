<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class NewsRepository
{
    public function latest(string $locale = 'ar', int $limit = 10): array
    {
        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                $sql = "SELECT id, title, slug, excerpt, image, published_at, updated_at, category_id
                        FROM news
                        WHERE deleted_at IS NULL
                        ORDER BY COALESCE(published_at, created_at, updated_at) DESC
                        LIMIT " . max(1, $limit);
                $stmt = $pdo->query($sql);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                return array_map([$this, 'normalize'], $rows);
            } catch (\Throwable) {}
        }
        return [[
            'id' => 1,
            'title' => 'Godyar v4 preview article',
            'slug' => 'godyar-v4-preview-article',
            'excerpt' => 'نسخة انتقالية لواجهة الأخبار في v4.',
            'content' => '<p>محتوى تجريبي لصفحة الخبر.</p>',
            'published_at' => date('c'),
            'category_slug' => 'general',
        ]];
    }

    public function findBySlug(string $slug, string $locale = 'ar'): ?array
    {
        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                $sql = "SELECT n.*, c.slug AS category_slug, c.name AS category_name
                        FROM news n
                        LEFT JOIN categories c ON c.id = n.category_id
                        WHERE n.slug = :slug
                        LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['slug' => $slug]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) { return $this->normalize($row); }
            } catch (\Throwable) {}
        }
        foreach ($this->latest($locale, 20) as $item) {
            if (($item['slug'] ?? '') === $slug) { return $item; }
        }
        return null;
    }

    public function findById(int $id, string $locale = 'ar'): ?array
    {
        if ($id <= 0) { return null; }
        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT n.*, c.slug AS category_slug, c.name AS category_name FROM news n LEFT JOIN categories c ON c.id = n.category_id WHERE n.id = :id LIMIT 1");
                $stmt->execute(['id' => $id]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) { return $this->normalize($row); }
            } catch (\Throwable) {}
        }
        foreach ($this->latest($locale, 20) as $item) { if ((int)($item['id'] ?? 0) === $id) return $item; }
        return null;
    }

    public function related(int $excludeId, string $categorySlug, string $locale = 'ar', int $limit = 4): array
    {
        return array_values(array_filter($this->latest($locale, $limit + 2), fn($n) => (int) ($n['id'] ?? 0) !== $excludeId)) ?: [];
    }

    public function byCategory(string $categorySlug, string $locale = 'ar', int $limit = 12): array
    {
        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                $sql = "SELECT n.*, c.slug AS category_slug, c.name AS category_name
                        FROM news n
                        INNER JOIN categories c ON c.id = n.category_id
                        WHERE c.slug = :slug
                        ORDER BY COALESCE(n.published_at, n.created_at, n.updated_at) DESC
                        LIMIT " . max(1, $limit);
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['slug' => $categorySlug]);
                return array_map([$this, 'normalize'], $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
            } catch (\Throwable) {}
        }
        return $this->latest($locale, $limit);
    }

    public function all(string $locale = 'ar', int $limit = 500): array
    {
        return $this->latest($locale, $limit);
    }

    private function normalize(array $row): array
    {
        $row['excerpt'] = $row['excerpt'] ?? $row['summary'] ?? '';
        $row['content'] = $row['content'] ?? $row['body'] ?? '<p>لا يوجد محتوى.</p>';
        $row['published_at'] = $row['published_at'] ?? $row['created_at'] ?? date('c');
        $row['updated_at'] = $row['updated_at'] ?? $row['published_at'];
        $row['category_slug'] = $row['category_slug'] ?? 'general';
        return $row;
    }
}
