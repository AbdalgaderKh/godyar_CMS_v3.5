<?php
declare(strict_types=1);

class PageController
{
    public static function show(string $slug): void
    {
        global $pdo;

        $lang = self::detectLang();
        $page = self::findPage($slug, $lang);

        if (!$page) {
            $page = [
                'title' => self::fallbackTitle($slug),
                'slug' => $slug,
                'content' => '',
                'lang' => $lang,
                'meta_title' => self::fallbackTitle($slug),
                'meta_description' => self::fallbackDescription($slug),
            ];
        }

        require __DIR__ . '/../views/page_view.php';
    }

    protected static function detectLang(): string
    {
        if (!empty($_GET['lang']) && is_string($_GET['lang'])) {
            return trim($_GET['lang']);
        }

        $uri = trim((string)($_SERVER['REQUEST_URI'] ?? ''), '/');
        $parts = explode('/', $uri);
        if (!empty($parts[0]) && in_array($parts[0], ['ar','en','fr'], true)) {
            return $parts[0];
        }

        return 'ar';
    }

    protected static function findPage(string $slug, string $lang): ?array
    {
        global $pdo;

        if (!$pdo instanceof PDO) {
            return null;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT *
                FROM pages
                WHERE slug = ?
                  AND status = 1
                  AND lang = ?
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([$slug, $lang]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        } catch (Throwable $e) {
        }

        try {
            $stmt = $pdo->prepare("
                SELECT *
                FROM pages
                WHERE slug = ?
                  AND status = 1
                  AND (lang IS NULL OR lang = '')
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([$slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    protected static function fallbackTitle(string $slug): string
    {
        return [
            'about'   => 'من نحن',
            'privacy' => 'سياسة الخصوصية',
            'terms'   => 'الشروط والأحكام',
            'contact' => 'اتصل بنا',
        ][$slug] ?? 'صفحة';
    }

    protected static function fallbackDescription(string $slug): string
    {
        return [
            'about'   => 'تعريف بالمنصة ورسالتها وأهدافها.',
            'privacy' => 'سياسة الخصوصية الخاصة بالموقع والمنصة.',
            'terms'   => 'الشروط والأحكام المنظمة لاستخدام الموقع.',
            'contact' => 'طرق التواصل مع إدارة الموقع.',
        ][$slug] ?? 'صفحة ثابتة ضمن منصة Godyar News Platform.';
    }
}
