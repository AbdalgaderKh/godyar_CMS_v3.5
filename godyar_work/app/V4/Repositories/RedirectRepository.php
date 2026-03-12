<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class RedirectRepository
{
    private function file(): string { return godyar_v4_storage_path('v4/redirects.json'); }
    private function readFileRows(): array { $f = $this->file(); return is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : []; }
    private function writeFileRows(array $rows): void { $f = $this->file(); if (!is_dir(dirname($f))) @mkdir(dirname($f), 0775, true); file_put_contents($f, json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); }

    public function all(): array
    {
        $rows = [];
        $pdo = godyar_v4_db();
        if ($pdo) {
            try {
                $stmt = $pdo->query('SELECT id, old_path, new_path, status_code, is_active, hits, created_at, updated_at FROM redirects ORDER BY updated_at DESC, id DESC');
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable) {}
        }
        $fileRows = $this->readFileRows();
        if (!$rows) {
            return array_map([$this, 'normalize'], $fileRows);
        }
        
        foreach ($fileRows as $row) {
            $key = $this->pathKey((string)($row['old_path'] ?? ''));
            $exists = false;
            foreach ($rows as $dbRow) {
                if ($this->pathKey((string)($dbRow['old_path'] ?? '')) === $key) { $exists = true; break; }
            }
            if (!$exists) $rows[] = $row;
        }
        return array_map([$this, 'normalize'], $rows);
    }

    public function findByPath(string $path): ?array
    {
        $needle = $this->pathKey($path);
        if ($needle === '') return null;
        foreach ($this->all() as $row) {
            if (!$this->isActive($row)) continue;
            if ($this->pathKey((string)($row['old_path'] ?? '')) === $needle) {
                return $row;
            }
        }
        return null;
    }

    public function upsert(array $payload, string $actor='system'): array
    {
        $payload = $this->normalize(array_merge($payload, ['updated_at' => date('c')]));
        $id = (string)($payload['id'] ?? '');
        $pdo = godyar_v4_db();
        if ($pdo && $this->dbTableExists($pdo)) {
            try {
                if ($id !== '' && ctype_digit($id)) {
                    $stmt = $pdo->prepare('UPDATE redirects SET old_path=:old_path,new_path=:new_path,status_code=:status_code,is_active=:is_active,updated_at=:updated_at WHERE id=:id');
                    $stmt->execute([
                        'id'=>(int)$id,
                        'old_path'=>$payload['old_path'],
                        'new_path'=>$payload['new_path'],
                        'status_code'=>(int)$payload['status_code'],
                        'is_active'=>(int)$payload['is_active'],
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ]);
                    return $payload;
                }
                $stmt = $pdo->prepare('INSERT INTO redirects (old_path,new_path,status_code,is_active,hits,created_at,updated_at) VALUES (:old_path,:new_path,:status_code,:is_active,0,:created_at,:updated_at)');
                $created = date('Y-m-d H:i:s');
                $stmt->execute([
                    'old_path'=>$payload['old_path'],'new_path'=>$payload['new_path'],'status_code'=>(int)$payload['status_code'],'is_active'=>(int)$payload['is_active'],'created_at'=>$created,'updated_at'=>$created,
                ]);
                $payload['id'] = (int)$pdo->lastInsertId();
                return $payload;
            } catch (\Throwable) {}
        }
        $rows = $this->readFileRows();
        $found = false;
        foreach ($rows as &$row) {
            $sameId = $id !== '' && (string)($row['id'] ?? '') === $id;
            $samePath = $this->pathKey((string)($row['old_path'] ?? '')) === $this->pathKey((string)$payload['old_path']);
            if ($sameId || $samePath) {
                $row = array_merge($this->normalize($row), $payload, ['updated_at'=>date('c')]);
                $payload['id'] = $row['id'];
                $found = true;
                break;
            }
        }
        unset($row);
        if (!$found) {
            $payload['id'] = $this->nextId($rows);
            $payload['created_at'] = date('c');
            $rows[] = $payload;
        }
        $this->writeFileRows($rows);
        return $payload;
    }

    public function delete(string|int $id): bool
    {
        $id = (string)$id;
        $pdo = godyar_v4_db();
        if ($pdo && ctype_digit($id) && $this->dbTableExists($pdo)) {
            try {
                $stmt = $pdo->prepare('DELETE FROM redirects WHERE id = :id');
                return $stmt->execute(['id'=>(int)$id]);
            } catch (\Throwable) {}
        }
        $rows = $this->readFileRows();
        $before = count($rows);
        $rows = array_values(array_filter($rows, fn($r) => (string)($r['id'] ?? '') !== $id));
        $this->writeFileRows($rows);
        return count($rows) !== $before;
    }

    public function incrementHits(string $path): void
    {
        $pathKey = $this->pathKey($path);
        if ($pathKey === '') return;
        $pdo = godyar_v4_db();
        if ($pdo && $this->dbTableExists($pdo)) {
            try {
                $stmt = $pdo->prepare('UPDATE redirects SET hits = COALESCE(hits,0) + 1, updated_at = :updated_at WHERE old_path = :old_path');
                $stmt->execute(['old_path'=>$pathKey,'updated_at'=>date('Y-m-d H:i:s')]);
                return;
            } catch (\Throwable) {}
        }
        $rows = $this->readFileRows();
        foreach ($rows as &$row) {
            if ($this->pathKey((string)($row['old_path'] ?? '')) === $pathKey) {
                $row['hits'] = (int)($row['hits'] ?? 0) + 1;
                $row['updated_at'] = date('c');
                break;
            }
        }
        unset($row);
        $this->writeFileRows($rows);
    }

    private function normalize(array $row): array
    {
        return [
            'id' => $row['id'] ?? '',
            'old_path' => $this->pathKey((string)($row['old_path'] ?? '')),
            'new_path' => (string)($row['new_path'] ?? '/'),
            'status_code' => in_array((int)($row['status_code'] ?? 301), [301,302,307,308], true) ? (int)($row['status_code'] ?? 301) : 301,
            'is_active' => (int)($row['is_active'] ?? 1),
            'hits' => (int)($row['hits'] ?? 0),
            'created_at' => (string)($row['created_at'] ?? ''),
            'updated_at' => (string)($row['updated_at'] ?? ''),
        ];
    }

    private function pathKey(string $path): string
    {
        $path = trim($path);
        if ($path === '') return '';
        $path = '/' . ltrim($path, '/');
        return rtrim($path, '/') ?: '/';
    }
    private function isActive(array $row): bool { return (int)($row['is_active'] ?? 1) === 1; }
    private function nextId(array $rows): int { $max = 0; foreach ($rows as $r) { $max = max($max, (int)($r['id'] ?? 0)); } return $max + 1; }
    private function dbTableExists(\PDO $pdo): bool
    {
        try { $pdo->query('SELECT 1 FROM redirects LIMIT 1'); return true; } catch (\Throwable) { return false; }
    }
}
