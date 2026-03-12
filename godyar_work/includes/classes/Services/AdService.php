<?php
namespace Godyar\Services;

use PDO;
use Throwable;

final class AdService
{
    public function __construct(private PDO $pdo) {}

    public function render(string $location, string $baseUrl = ''): string
    {
        $location = trim($location);
        if ($location === '') {
            return '';
        }

        $baseUrl = rtrim($baseUrl, '/');

        try {
            if (!$this->tableExists('ads')) {
                return '';
            }

            $cols = $this->getColumns('ads');

            $colId = $this->pick($cols, ['id', 'ad_id']) ?? 'id';
            $colTitle = $this->pick($cols, ['title', 'name', 'ad_title']);
            $colLocation = $this->pick($cols, ['location', 'loc', 'placement', 'position', 'slot']);
            $colImage = $this->pick($cols, ['image', 'image_url', 'img', 'banner', 'banner_url', 'picture', 'photo', 'file', 'path']);
            $colUrl = $this->pick($cols, ['url', 'target_url', 'link', 'href', 'redirect_url', 'click_url']);
            $colType = $this->pick($cols, ['ad_type', 'type', 'content_type']);
            $colContent = $this->pick($cols, ['content', 'html', 'html_code', 'code', 'body']);
            $colActive = $this->pick($cols, ['is_active', 'active', 'status', 'enabled']);

            $where = [];
            $params = [];

            if ($colLocation) {
                $where[] = "`$colLocation` = :loc";
                $params[':loc'] = $location;
            }

            if ($colActive) {
                if ($colActive === 'status') {
                    $where[] = "(`$colActive` = 'active' OR `$colActive` = 1)";
                } else {
                    $where[] = "(`$colActive` = 1)";
                }
            }

            $sql = 'SELECT * FROM ads';
            if ($where) {
                $sql .= ' WHERE ' .implode(' AND ', $where);
            }
            $sql .= " ORDER BY `$colId` DESC LIMIT 1";

            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $ad = $st->fetch(PDO::FETCH_ASSOC);
            if (!$ad) {
                return '';
            }

            $title = $colTitle && isset($ad[$colTitle]) ? trim((string)$ad[$colTitle]) : '';
            $image = $colImage && isset($ad[$colImage]) ? trim((string)$ad[$colImage]) : '';
            $url = $colUrl && isset($ad[$colUrl]) ? trim((string)$ad[$colUrl]) : '';
            $type = $colType && isset($ad[$colType]) ? strtolower(trim((string)$ad[$colType])) : '';
            $html = $colContent && isset($ad[$colContent]) ? (string)$ad[$colContent] : '';

            
            if ($image !== '' && !preg_match('~^https?://~i', $image) && $baseUrl !== '') {
                $image = $baseUrl . '/' .ltrim($image, '/');
            }

            if ($url !== '' && !preg_match('~^https?://~i', $url) && $baseUrl !== '') {
                $url = $baseUrl . '/' .ltrim($url, '/');
            }

            
            if ($type === 'html' && $html !== '') {
                return $html; 
            }

            
            if ($image !== '') {
                $alt = htmlspecialchars($title !== '' ? $title : 'Ad', ENT_QUOTES, 'UTF-8');
                $imgTag = '<img src="' .htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '" loading="lazy">';
                if ($url !== '') {
                    return '<a href="' .htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" rel="noopener" target="_blank">' . $imgTag . '</a>';
                }
                return $imgTag;
            }

            return '';
        } catch (\Throwable $e) {
            error_log('[AdService] ' . $e->getMessage());
            return '';
        }
    }

    
    private function getColumns(string $table): array
    {
        try {
            $st = $this->pdo->prepare('SHOW COLUMNS FROM `' .str_replace('`', '', $table) . '`');
            $st->execute();
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $cols = [];
            foreach ($rows as $r) {
                if (isset($r['Field'])) {
                    $cols[] = (string)$r['Field'];
                }
            }
            return $cols;
        } catch (Throwable) {
            return [];
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $st = $this->pdo->prepare('SHOW TABLES LIKE :t');
            $st->execute([':t' => $table]);
            return (bool)$st->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }

    
    private function pick(array $cols, array $candidates): ?string
    {
        $lookup = [];
        foreach ($cols as $c) {
            $lookup[strtolower($c)] = $c;
        }
        foreach ($candidates as $cand) {
            $k = strtolower((string)$cand);
            if (isset($lookup[$k])) {
                return $lookup[$k];
            }
        }
        return null;
    }
}
