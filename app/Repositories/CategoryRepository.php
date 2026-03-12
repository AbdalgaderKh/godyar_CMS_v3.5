<?php

declare(strict_types=1);

namespace GodyarV4\Repositories;

use GodyarV4\Support\LegacyDbBridge;

final class CategoryRepository
{
    public function findBySlug(string $slug, string $lang = 'ar'): ?array
    {
        if (LegacyDbBridge::tableExists('categories')) {
            $sql = 'SELECT id, name, slug, lang, parent_id, sort_order, is_active, created_at, updated_at
                    FROM categories
                    WHERE slug = :slug AND is_active = 1';
            $params = ['slug' => $slug];

            if (LegacyDbBridge::columnExists('categories', 'lang')) {
                $sql .= ' AND (lang = :lang OR lang IS NULL OR lang = "")';
                $params['lang'] = $lang;
            }

            $sql .= ' ORDER BY CASE WHEN lang = :lang_exact THEN 0 ELSE 1 END, id DESC LIMIT 1';
            $params['lang_exact'] = $lang;

            $row = LegacyDbBridge::fetchOne($sql, $params);
            if ($row) {
                return [
                    'id' => $row['id'] ?? null,
                    'title' => $row['name'] ?? $slug,
                    'slug' => $row['slug'] ?? $slug,
                    'description' => 'أرشيف التصنيف الموحد في v4.',
                    'lang' => $row['lang'] ?? $lang,
                    'source' => 'legacy-db',
                ];
            }
        }

        return [
            'title' => 'تصنيف: ' . $slug,
            'slug' => $slug,
            'description' => 'عرض تجريبي للتصنيف في v4.',
            'lang' => $lang,
            'source' => 'fallback',
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function latestNewsByCategorySlug(string $slug, string $lang = 'ar', int $limit = 12): array
    {
        if (!LegacyDbBridge::tableExists('news') || !LegacyDbBridge::tableExists('categories')) {
            return [];
        }

        $sql = 'SELECT n.id, n.title, n.slug, n.excerpt, n.published_at
                FROM news n
                INNER JOIN categories c ON c.id = n.category_id
                WHERE c.slug = :slug
                  AND n.deleted_at IS NULL
                  AND (n.is_published = 1 OR n.status = "published")';
        $params = ['slug' => $slug];

        if (LegacyDbBridge::columnExists('categories', 'lang')) {
            $sql .= ' AND (c.lang = :lang OR c.lang IS NULL OR c.lang = "")';
            $params['lang'] = $lang;
        }

        $sql .= ' ORDER BY COALESCE(n.published_at, n.created_at) DESC LIMIT ' . max(1, (int)$limit);

        return LegacyDbBridge::fetchAll($sql, $params);
    }
}
