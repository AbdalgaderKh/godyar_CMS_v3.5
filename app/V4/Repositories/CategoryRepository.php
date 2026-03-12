<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class CategoryRepository
{
    public function findBySlug(string $slug, string $locale = 'ar'): ?array
    {
        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT id, name, slug, description, updated_at FROM categories WHERE slug = :slug LIMIT 1");
                $stmt->execute(['slug' => $slug]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) { return $row; }
            } catch (\Throwable) {}
        }
        return ['id' => 0, 'name' => ucfirst($slug), 'slug' => $slug, 'description' => 'تصنيف انتقالي ضمن v4.', 'updated_at' => date('c')];
    }

    public function all(string $locale = 'ar', int $limit = 200): array
    {
        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT slug, updated_at FROM categories ORDER BY id DESC LIMIT " . max(1, $limit));
                return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable) {}
        }
        return [['slug' => 'general', 'updated_at' => date('c')]];
    }
}
