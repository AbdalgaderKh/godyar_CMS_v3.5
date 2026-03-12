<?php

declare(strict_types=1);

namespace GodyarV4\Repositories;

use GodyarV4\Support\LegacyDbBridge;

final class PageRepository
{
    public function findBySlug(string $slug, string $lang = 'ar'): ?array
    {
        if (LegacyDbBridge::tableExists('pages')) {
            $row = LegacyDbBridge::fetchOne(
                'SELECT id, title, slug, content, status, created_at, updated_at
                 FROM pages
                 WHERE slug = :slug AND status = :status
                 LIMIT 1',
                ['slug' => $slug, 'status' => 'published']
            );

            if ($row) {
                $row['lang'] = $lang;
                $row['source'] = 'legacy-db';
                return $row;
            }
        }

        $pages = [
            'about' => ['title' => 'من نحن', 'content' => 'هذه صفحة تعريفية مؤقتة لهيكل v4.'],
            'privacy' => ['title' => 'سياسة الخصوصية', 'content' => 'سياسة الخصوصية الموحدة في v4.'],
            'terms' => ['title' => 'الشروط والأحكام', 'content' => 'نص الشروط والأحكام الموحد في v4.'],
            'contact' => ['title' => 'اتصل بنا', 'content' => 'يمكنك التواصل معنا عبر النموذج المخصص في v4.'],
        ];

        if (!isset($pages[$slug])) {
            return null;
        }

        return $pages[$slug] + ['slug' => $slug, 'lang' => $lang, 'source' => 'fallback'];
    }
}
