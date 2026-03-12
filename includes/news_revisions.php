<?php
declare(strict_types=1);

if (!function_exists('gdy_news_revisions_table_exists')) {
    function gdy_news_revisions_table_exists(PDO $pdo): bool
    {
        try {
            $st = $pdo->query("SHOW TABLES LIKE 'news_revisions'");
            return (bool)$st->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('gdy_save_news_revision')) {
    function gdy_save_news_revision(PDO $pdo, int $newsId, ?int $userId, array $data): bool
    {
        if ($newsId <= 0 || !gdy_news_revisions_table_exists($pdo)) {
            return false;
        }

        try {
            $sql = "INSERT INTO news_revisions
                (news_id, user_id, title, slug, excerpt, content, status, created_at)
                VALUES
                (:news_id, :user_id, :title, :slug, :excerpt, :content, :status, NOW())";

            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':news_id' => $newsId,
                ':user_id' => $userId,
                ':title'   => (string)($data['title'] ?? ''),
                ':slug'    => (string)($data['slug'] ?? ''),
                ':excerpt' => (string)($data['excerpt'] ?? ''),
                ':content' => (string)($data['content'] ?? ''),
                ':status'  => (string)($data['status'] ?? ''),
            ]);
        } catch (Throwable $e) {
            error_log('Save revision failed: ' . $e->getMessage());
            return false;
        }
    }
}