<?php

declare(strict_types=1);

namespace GodyarV4\Repositories;

use GodyarV4\Support\LegacyDbBridge;

final class NewsRepository
{
    /** @return array<int,array<string,mixed>> */
    public function latest(int $limit = 6, string $lang = 'ar'): array
    {
        if (LegacyDbBridge::tableExists('news')) {
            $rows = LegacyDbBridge::fetchAll(
                'SELECT id, title, slug, excerpt, published_at, created_at,
                        COALESCE(featured_image, image_path, image) AS image,
                        category_id,
                        views,
                        view_count,
                        is_breaking
                 FROM news
                 WHERE deleted_at IS NULL
                   AND (is_published = 1 OR status = "published")
                 ORDER BY COALESCE(published_at, created_at) DESC
                 LIMIT ' . max(1, (int)$limit)
            );
            if ($rows) {
                foreach ($rows as &$row) {
                    $row['lang'] = $lang;
                    $row['source'] = 'legacy-db';
                }
                return $rows;
            }
        }

        $items = [];
        for ($i = 1; $i <= $limit; $i++) {
            $items[] = [
                'title' => 'خبر تجريبي #' . $i,
                'slug' => 'demo-news-' . $i,
                'excerpt' => 'هذا محتوى تجريبي لإثبات هيكل v4.',
                'published_at' => date('Y-m-d H:i:s', strtotime('-' . $i . ' hours')),
                'lang' => $lang,
                'source' => 'fallback',
            ];
        }
        return $items;
    }

    public function findBySlug(string $slug, string $lang = 'ar'): ?array
    {
        if (LegacyDbBridge::tableExists('news')) {
            $row = LegacyDbBridge::fetchOne(
                'SELECT n.id, n.title, n.slug, n.excerpt, n.content, n.published_at, n.created_at,
                        n.seo_title, n.seo_description,
                        n.category_id, n.author_id,
                        n.views, n.view_count, n.is_breaking,
                        COALESCE(n.featured_image, n.image_path, n.image) AS image,
                        c.name AS category_name,
                        c.slug AS category_slug,
                        COALESCE(u.name, u.username, "Godyar") AS author_name
                 FROM news n
                 LEFT JOIN categories c ON c.id = n.category_id
                 LEFT JOIN users u ON u.id = n.author_id
                 WHERE n.slug = :slug
                   AND n.deleted_at IS NULL
                   AND (n.is_published = 1 OR n.status = "published")
                 LIMIT 1',
                ['slug' => $slug]
            );
            if ($row) {
                $row['author'] = $row['author_name'] ?? 'Godyar';
                $row['source'] = 'legacy-db';
                $row['lang'] = $lang;
                return $row;
            }
        }

        return [
            'title' => 'خبر تجريبي: ' . $slug,
            'slug' => $slug,
            'excerpt' => 'ملخص افتراضي للخبر.',
            'content' => 'هذا عرض افتراضي لصفحة الخبر داخل هيكل Godyar CMS v4.',
            'published_at' => date('Y-m-d H:i:s'),
            'author' => 'Godyar Team',
            'lang' => $lang,
            'source' => 'fallback',
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function related(int $categoryId, int $excludeId, int $limit = 4): array
    {
        if (!LegacyDbBridge::tableExists('news')) {
            return [];
        }

        return LegacyDbBridge::fetchAll(
            'SELECT id, title, slug, excerpt, published_at,
                    COALESCE(featured_image, image_path, image) AS image
             FROM news
             WHERE deleted_at IS NULL
               AND (is_published = 1 OR status = "published")
               AND category_id = :category_id
               AND id <> :exclude_id
             ORDER BY COALESCE(published_at, created_at) DESC
             LIMIT ' . max(1, (int)$limit),
            ['category_id' => $categoryId, 'exclude_id' => $excludeId]
        );
    }
}
