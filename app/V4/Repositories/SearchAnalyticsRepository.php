<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class SearchAnalyticsRepository
{
    private function filePath(): string
    {
        return godyar_v4_storage_path('v4/search_analytics.json');
    }

    private function readRows(): array
    {
        $file = $this->filePath();
        if (!is_file($file)) {
            return [];
        }
        $json = (string)@file_get_contents($file);
        $rows = json_decode($json, true);
        return is_array($rows) ? $rows : [];
    }

    private function writeRows(array $rows): void
    {
        $file = $this->filePath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($file, json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function log(string $query, int $results, string $locale = 'ar'): void
    {
        $query = trim($query);
        if ($query === '') {
            return;
        }

        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                if (function_exists('gdy_db_table_exists') && gdy_db_table_exists($pdo, 'search_queries')) {
                    $stmt = $pdo->prepare('INSERT INTO search_queries (query, results, locale, created_at) VALUES (:query, :results, :locale, NOW())');
                    $stmt->execute([
                        'query' => mb_substr($query, 0, 190),
                        'results' => $results,
                        'locale' => mb_substr($locale, 0, 8),
                    ]);
                    return;
                }
            } catch (\Throwable) {
            }
        }

        $rows = $this->readRows();
        $rows[] = [
            'query' => mb_substr($query, 0, 190),
            'results' => max(0, $results),
            'locale' => mb_substr($locale, 0, 8),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $limit = 1500;
        if (count($rows) > $limit) {
            $rows = array_slice($rows, -$limit);
        }
        $this->writeRows($rows);
    }

    public function summary(int $limit = 10): array
    {
        $rows = $this->fetchRows(2500);
        $top = [];
        $noResults = [];
        $daily = [];
        $today = date('Y-m-d');

        foreach ($rows as $row) {
            $query = trim((string)($row['query'] ?? ''));
            if ($query === '') {
                continue;
            }
            $key = function_exists('mb_strtolower') ? mb_strtolower($query) : strtolower($query);
            if (!isset($top[$key])) {
                $top[$key] = ['query' => $query, 'count' => 0, 'hits' => 0, 'last_at' => ''];
            }
            $top[$key]['count']++;
            $top[$key]['hits'] += (int)($row['results'] ?? 0);
            $top[$key]['last_at'] = max((string)$top[$key]['last_at'], (string)($row['created_at'] ?? ''));

            if ((int)($row['results'] ?? 0) <= 0) {
                if (!isset($noResults[$key])) {
                    $noResults[$key] = ['query' => $query, 'count' => 0, 'last_at' => ''];
                }
                $noResults[$key]['count']++;
                $noResults[$key]['last_at'] = max((string)$noResults[$key]['last_at'], (string)($row['created_at'] ?? ''));
            }

            if (str_starts_with((string)($row['created_at'] ?? ''), $today)) {
                if (!isset($daily[$key])) {
                    $daily[$key] = ['query' => $query, 'count' => 0];
                }
                $daily[$key]['count']++;
            }
        }

        uasort($top, static fn(array $a, array $b): int => ($b['count'] <=> $a['count']) ?: strcmp((string)$b['last_at'], (string)$a['last_at']));
        uasort($noResults, static fn(array $a, array $b): int => ($b['count'] <=> $a['count']) ?: strcmp((string)$b['last_at'], (string)$a['last_at']));
        uasort($daily, static fn(array $a, array $b): int => $b['count'] <=> $a['count']);

        return [
            'top' => array_slice(array_values($top), 0, $limit),
            'no_results' => array_slice(array_values($noResults), 0, $limit),
            'today' => array_slice(array_values($daily), 0, $limit),
            'recent' => array_slice(array_reverse($rows), 0, $limit),
        ];
    }

    private function fetchRows(int $limit = 2500): array
    {
        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                if (function_exists('gdy_db_table_exists') && gdy_db_table_exists($pdo, 'search_queries')) {
                    $stmt = $pdo->query('SELECT query, results, locale, created_at FROM search_queries ORDER BY created_at DESC LIMIT ' . max(1, $limit));
                    return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                }
            } catch (\Throwable) {
            }
        }
        $rows = $this->readRows();
        if (count($rows) > $limit) {
            $rows = array_slice($rows, -$limit);
        }
        return $rows;
    }
}
