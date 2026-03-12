<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class PageRepository
{
    public function findBySlug(string $slug, string $locale = 'ar'): ?array
    {
        $pdo = godyar_v4_db();
        if ($pdo) {
            $sql = "SELECT id, title, slug, content, updated_at, COALESCE(lang, :locale) AS lang
                    FROM pages
                    WHERE slug = :slug
                    ORDER BY id DESC LIMIT 1";
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['slug' => $slug, 'locale' => $locale]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) { return $row; }
            } catch (\Throwable) {}
        }

        $static = [
            'about' => ['id' => 0, 'title' => 'من نحن', 'slug' => 'about', 'content' => '<p>هذه صفحة تعريفية انتقالية ضمن v4.</p>', 'lang' => $locale],
            'privacy' => ['id' => 0, 'title' => 'سياسة الخصوصية', 'slug' => 'privacy', 'content' => '<p>سياسة الخصوصية ستعرض هنا.</p>', 'lang' => $locale],
            'terms' => ['id' => 0, 'title' => 'الشروط والأحكام', 'slug' => 'terms', 'content' => '<p>الشروط والأحكام ستعرض هنا.</p>', 'lang' => $locale],
        ];
        return $static[$slug] ?? null;
    }

    public function all(string $locale = 'ar', int $limit = 500): array
    {
        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT slug, updated_at FROM pages ORDER BY id DESC LIMIT " . max(1, $limit));
                return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable) {}
        }
        return [
            ['slug' => 'about', 'updated_at' => date('c')],
            ['slug' => 'privacy', 'updated_at' => date('c')],
            ['slug' => 'terms', 'updated_at' => date('c')],
        ];
    }
}
