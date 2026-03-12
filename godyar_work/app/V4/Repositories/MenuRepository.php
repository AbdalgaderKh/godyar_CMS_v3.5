<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class MenuRepository
{
    public function get(string $position, string $locale = 'ar'): array
    {
        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'menus'");
                if ($tableCheck && $tableCheck->fetchColumn()) {
                    $stmt = $pdo->prepare("SELECT label, url, sort_order FROM menus WHERE position = :position ORDER BY sort_order ASC, id ASC");
                    $stmt->execute(['position' => $position]);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    if ($rows) {
                        return array_map(static fn(array $row) => [
                            'label' => $row['label'],
                            'url' => $row['url'],
                        ], $rows);
                    }
                }
            } catch (\Throwable) {}
        }

        return [
            ['label' => 'الرئيسية', 'url' => godyar_v4_url($locale)],
            ['label' => 'من نحن', 'url' => godyar_v4_url($locale, 'page/about')],
            ['label' => 'الخصوصية', 'url' => godyar_v4_url($locale, 'page/privacy')],
            ['label' => 'الشروط', 'url' => godyar_v4_url($locale, 'page/terms')],
            ['label' => 'تواصل', 'url' => godyar_v4_url($locale, 'contact')],
        ];
    }
}
