<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

use PDO;

final class ContactMessageRepository
{
    public function save(array $payload): bool
    {
        $pdo = godyar_v4_db();
        if (!$pdo) {
            return $this->storeFallback($payload);
        }

        try {
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'contact_messages'");
            if (!$tableCheck || !$tableCheck->fetchColumn()) {
                return $this->storeFallback($payload);
            }

            $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, subject, message, locale, ip_address, user_agent, source_url, created_at) VALUES (:name,:email,:subject,:message,:locale,:ip_address,:user_agent,:source_url,NOW())');
            return $stmt->execute([
                ':name' => (string)($payload['name'] ?? ''),
                ':email' => (string)($payload['email'] ?? ''),
                ':subject' => (string)($payload['subject'] ?? ''),
                ':message' => (string)($payload['message'] ?? ''),
                ':locale' => (string)($payload['locale'] ?? 'ar'),
                ':ip_address' => (string)($payload['ip_address'] ?? ''),
                ':user_agent' => (string)($payload['user_agent'] ?? ''),
                ':source_url' => (string)($payload['source_url'] ?? ''),
            ]);
        } catch (\Throwable) {
            return $this->storeFallback($payload);
        }
    }

    public function latest(int $limit = 50): array
    {
        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'contact_messages'");
                if ($tableCheck && $tableCheck->fetchColumn()) {
                    $stmt = $pdo->prepare('SELECT id, name, email, subject, message, locale, ip_address, source_url, created_at FROM contact_messages ORDER BY id DESC LIMIT :limit');
                    $stmt->bindValue(':limit', max(1, min(200, $limit)), PDO::PARAM_INT);
                    $stmt->execute();
                    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
            } catch (\Throwable) {
            }
        }

        $file = godyar_v4_storage_path('reports/contact_messages.log');
        if (!is_file($file)) {
            return [];
        }
        $rows = [];
        foreach (array_reverse(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) as $line) {
            $row = json_decode((string)$line, true);
            if (is_array($row)) {
                $rows[] = $row;
            }
            if (count($rows) >= $limit) {
                break;
            }
        }
        return $rows;
    }

    private function storeFallback(array $payload): bool
    {
        $file = godyar_v4_storage_path('reports/contact_messages.log');
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $payload['created_at'] = $payload['created_at'] ?? date('c');
        return false !== @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    }
}
