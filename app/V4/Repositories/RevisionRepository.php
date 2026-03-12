<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class RevisionRepository
{
    public function forNews(int $newsId, int $limit = 10): array
    {
        if ($newsId <= 0) { return []; }
        $file = godyar_v4_storage_path('revisions/news-' . $newsId . '.json');
        if (!is_file($file)) { return []; }
        $decoded = json_decode((string) file_get_contents($file), true);
        $rows = is_array($decoded) ? $decoded : [];
        return array_slice(array_map([$this, 'normalize'], $rows), 0, $limit);
    }

    public function getSnapshot(int $newsId, string $revisionId): ?array
    {
        foreach ($this->forNews($newsId, 50) as $row) {
            if ((string)($row['id'] ?? '') === $revisionId) { return $row; }
        }
        return null;
    }

    public function restoreDraft(int $newsId, string $revisionId, string $actor = 'system'): array
    {
        $snapshot = $this->getSnapshot($newsId, $revisionId);
        if (!$snapshot) { return ['ok' => false, 'message' => 'النسخة المطلوبة غير موجودة.']; }
        $dir = godyar_v4_storage_path('v4/restores');
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $file = $dir . '/news-' . $newsId . '-draft.json';
        $ok = (bool) @file_put_contents($file, json_encode([
            'mode' => 'draft',
            'restored_at' => date('c'),
            'restored_by' => $actor,
            'snapshot' => $snapshot,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return ['ok' => $ok, 'message' => $ok ? 'تم إنشاء Draft restore آمن.' : 'تعذر إنشاء Draft restore.', 'file' => $file];
    }

    public function restoreLive(int $newsId, string $revisionId, string $actor = 'system'): array
    {
        $snapshot = $this->getSnapshot($newsId, $revisionId);
        if (!$snapshot) { return ['ok' => false, 'message' => 'النسخة المطلوبة غير موجودة.']; }
        $repo = new NewsRepository();
        $current = $repo->findById($newsId) ?? ['id' => $newsId];
        $backupDir = godyar_v4_storage_path('v4/backups');
        if (!is_dir($backupDir)) { @mkdir($backupDir, 0775, true); }
        @file_put_contents($backupDir . '/news-' . $newsId . '-' . date('Ymd-His') . '.json', json_encode([
            'backed_up_at' => date('c'),
            'backed_up_by' => $actor,
            'news' => $current,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $pdo = godyar_v4_db();
        if (!$pdo) {
            return ['ok' => false, 'message' => 'لا يوجد اتصال قاعدة بيانات لتطبيق الاستعادة الحية.'];
        }
        $payload = [
            'id' => $newsId,
            'title' => (string)($snapshot['title'] ?? ''),
            'excerpt' => (string)($snapshot['excerpt'] ?? ''),
            'summary' => (string)($snapshot['summary'] ?? ''),
            'body' => (string)($snapshot['body'] ?: $snapshot['content'] ?? ''),
            'content' => (string)($snapshot['content'] ?: $snapshot['body'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        foreach ([
            "UPDATE news SET title=:title, excerpt=:excerpt, summary=:summary, body=:body, content=:content, updated_at=:updated_at WHERE id=:id",
            "UPDATE posts SET title=:title, excerpt=:excerpt, summary=:summary, body=:body, content=:content, updated_at=:updated_at WHERE id=:id",
        ] as $sql) {
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($payload);
                if ($stmt->rowCount() > 0) {
                    @file_put_contents(godyar_v4_storage_path('reports/revision_restore.log'), '[' . date('c') . '] LIVE news_id=' . $newsId . ' revision=' . $revisionId . ' by=' . $actor . PHP_EOL, FILE_APPEND);
                    return ['ok' => true, 'message' => 'تم تنفيذ live restore مع إنشاء backup snapshot.'];
                }
            } catch (\Throwable) {}
        }
        return ['ok' => false, 'message' => 'تعذر تطبيق الاستعادة الحية على الجداول المعروفة.'];
    }

    private function normalize(array $row): array
    {
        return [
            'id' => (string)($row['id'] ?? ('rev_' . substr(hash('sha256', microtime(true) . '|' . getmypid() . '|' . json_encode($row)), 0, 24))),
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
