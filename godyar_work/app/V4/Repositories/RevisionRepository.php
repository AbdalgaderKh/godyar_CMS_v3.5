<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class RevisionRepository
{
    public function forNews(int $newsId, int $limit = 10): array
    {
        if ($newsId <= 0) {
            return [];
        }

        $file = godyar_v4_storage_path('revisions/news-' . $newsId . '.json');
        if (gdy_is_file($file) === false) {
            return [];
        }

        $json = gdy_file_get_contents($file);
        $decoded = is_string($json) ? json_decode($json, true) : null;
        $rows = is_array($decoded) ? $decoded : [];

        return array_slice(array_map([$this, 'normalize'], $rows), 0, $limit);
    }

    public function getSnapshot(int $newsId, string $revisionId): ?array
    {
        foreach ($this->forNews($newsId, 50) as $row) {
            if ((string)($row['id'] ?? '') === $revisionId) {
                return $row;
            }
        }

        return null;
    }

    public function restoreDraft(int $newsId, string $revisionId, string $actor = 'system'): array
    {
        $snapshot = $this->getSnapshot($newsId, $revisionId);
        if (is_array($snapshot) === false) {
            return ['ok' => false, 'message' => 'النسخة المطلوبة غير موجودة.'];
        }

        $dir = godyar_v4_storage_path('v4/restores');
        if (gdy_is_dir($dir) === false) {
            gdy_mkdir($dir, 0755, true);
        }

        $file = $dir . '/news-' . $newsId . '-draft.json';
        $payload = json_encode([
            'mode' => 'draft',
            'restored_at' => date('c'),
            'restored_by' => $actor,
            'snapshot' => $snapshot,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $ok = is_string($payload) && gdy_file_put_contents($file, $payload, LOCK_EX) > 0;

        return [
            'ok' => $ok,
            'message' => $ok ? 'تم إنشاء Draft restore آمن.' : 'تعذر إنشاء Draft restore.',
            'file' => $file,
        ];
    }

    public function restoreLive(int $newsId, string $revisionId, string $actor = 'system'): array
    {
        $snapshot = $this->getSnapshot($newsId, $revisionId);
        if (is_array($snapshot) === false) {
            return ['ok' => false, 'message' => 'النسخة المطلوبة غير موجودة.'];
        }

        $repo = new NewsRepository();
        $current = $repo->findById($newsId) ?? ['id' => $newsId];

        $backupDir = godyar_v4_storage_path('v4/backups');
        if (gdy_is_dir($backupDir) === false) {
            gdy_mkdir($backupDir, 0755, true);
        }

        $backupPayload = json_encode([
            'backed_up_at' => date('c'),
            'backed_up_by' => $actor,
            'news' => $current,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (is_string($backupPayload) === true) {
            gdy_file_put_contents($backupDir . '/news-' . $newsId . '-' . date('Ymd-His') . '.json', $backupPayload, LOCK_EX);
        }

        $pdo = godyar_v4_db();
        if (!$pdo) {
            return ['ok' => false, 'message' => 'لا يوجد اتصال قاعدة بيانات لتطبيق الاستعادة الحية.'];
        }

        $body = (string)($snapshot['body'] ?? '');
        $content = (string)($snapshot['content'] ?? '');
        $payload = [
            'id' => $newsId,
            'title' => (string)($snapshot['title'] ?? ''),
            'excerpt' => (string)($snapshot['excerpt'] ?? ''),
            'summary' => (string)($snapshot['summary'] ?? ''),
            'body' => $body !== '' ? $body : $content,
            'content' => $content !== '' ? $content : $body,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        foreach ([
            'UPDATE news SET title=:title, excerpt=:excerpt, summary=:summary, body=:body, content=:content, updated_at=:updated_at WHERE id=:id',
            'UPDATE posts SET title=:title, excerpt=:excerpt, summary=:summary, body=:body, content=:content, updated_at=:updated_at WHERE id=:id',
        ] as $sql) {
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($payload);
                if ($stmt->rowCount() > 0) {
                    gdy_file_put_contents(
                        godyar_v4_storage_path('reports/revision_restore.log'),
                        '[' . date('c') . '] LIVE news_id=' . $newsId . ' revision=' . $revisionId . ' by=' . $actor . PHP_EOL,
                        FILE_APPEND
                    );
                    return ['ok' => true, 'message' => 'تم تنفيذ live restore مع إنشاء backup snapshot.'];
                }
            } catch (\Throwable) {
            }
        }

        return ['ok' => false, 'message' => 'تعذر تطبيق الاستعادة الحية على الجداول المعروفة.'];
    }

    private function normalize(array $row): array
    {
        $id = isset($row['id']) ? (string)$row['id'] : '';
        if ($id === '') {
            try {
                $id = 'rev_' . bin2hex(random_bytes(8));
            } catch (\Throwable) {
                $id = 'rev_' . substr(hash('sha256', uniqid('rev_', true)), 0, 16);
            }
        }

        return [
            'id' => $id,
            'title' => (string)($row['title'] ?? ''),
            'excerpt' => (string)($row['excerpt'] ?? ''),
            'summary' => (string)($row['summary'] ?? ''),
            'body' => (string)($row['body'] ?? ''),
            'content' => (string)($row['content'] ?? ''),
            'editor_name' => (string)($row['editor_name'] ?? 'unknown'),
            'created_at' => (string)($row['created_at'] ?? ''),
            'note' => (string)($row['note'] ?? ''),
        ];
    }
}
