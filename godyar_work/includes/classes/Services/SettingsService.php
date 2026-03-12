<?php

namespace Godyar\Services;

use PDO;
use Godyar\DB;

final class SettingsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

private function loadAllCached(int $ttl = 600): array
{
    $loader = function (): array {
        $out = [];

        $col = function_exists('gdy_settings_value_column')
            ? gdy_settings_value_column($this->pdo)
            : 'value';

        
        $colIdent = function_exists('gdy_sql_ident')
            ? gdy_sql_ident($this->pdo, $col, ['setting_value','value','val','v','value_text','setting_val','setting_data'], 'value')
            : $col;

        
        $st = $this->pdo->query("SELECT setting_key, {$colIdent} AS value FROM settings");

        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $k = (string)($row['setting_key'] ?? '');
            if ($k === '') { continue; }
            $v = $row['value'] ?? null;
            if (is_string($v)) {
                $decoded = json_decode($v, true);
                $out[$k] = ($decoded === null && json_last_error() !== JSON_ERROR_NONE) ? $v : $decoded;
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    };

    if (class_exists('\\Cache')) {
        return \Cache::remember('settings_all_v1', $ttl, $loader);
    }
    return $loader();
}

    public function getValue(string $key, $default = null)
{
    try {
        $all = $this->loadAllCached(600);
        return array_key_exists($key, $all) ? $all[$key] : $default;
    } catch (\Throwable $e) {
        return $default;
    }
}

    public function setValue(string $key, $value): void
    {
        $col = function_exists('gdy_settings_value_column') ? gdy_settings_value_column($this->pdo) : 'value';
        $hasUpdatedAt = false;
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_COLUMN);
            $hasUpdatedAt = (is_array($cols) && in_array('updated_at', $cols, true));
        } catch (\Throwable $e) { $hasUpdatedAt = false; }
        $val = is_array($value)
            ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            : (string)$value;

        
        
        
        $now = date('Y-m-d H:i:s');

        $this->pdo->beginTransaction();
        try {
            $setSql = $hasUpdatedAt
                ? "UPDATE settings SET {$col} = :v, updated_at = :u WHERE setting_key = :k"
                : "UPDATE settings SET {$col} = :v WHERE setting_key = :k";
            $st = $this->pdo->prepare($setSql);
            $params = [':k' => $key, ':v' => $val];
            if ($hasUpdatedAt) { $params[':u'] = $now; }
            $st->execute($params);

            if ((int)$st->rowCount() === 0) {
                $insSql = $hasUpdatedAt
                    ? "INSERT INTO settings (setting_key, {$col}, updated_at) VALUES (:k, :v, :u)"
                    : "INSERT INTO settings (setting_key, {$col}) VALUES (:k, :v)";
                $ins = $this->pdo->prepare($insSql);
                $insParams = [':k' => $key, ':v' => $val];
                if ($hasUpdatedAt) { $insParams[':u'] = $now; }
                $ins->execute($insParams);
            }

            
            
            if ($hasUpdatedAt) {
                $dedupeSql = "DELETE s FROM settings s\n" .
                             "JOIN (SELECT setting_key, MAX(COALESCE(updated_at,'1970-01-01 00:00:00')) AS mx\n" .
                             "      FROM settings WHERE setting_key = :k GROUP BY setting_key) t\n" .
                             "  ON s.setting_key = t.setting_key\n" .
                             " AND COALESCE(s.updated_at,'1970-01-01 00:00:00') < t.mx\n" .
                             "WHERE s.setting_key = :k";
                $dd = $this->pdo->prepare($dedupeSql);
                $dd->execute([':k' => $key]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
}

    
    public function setMany(array $pairs): void
    {
        $col = function_exists('gdy_settings_value_column') ? gdy_settings_value_column($this->pdo) : 'value';
        $hasUpdatedAt = false;
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_COLUMN);
            $hasUpdatedAt = (is_array($cols) && in_array('updated_at', $cols, true));
        } catch (\Throwable $e) { $hasUpdatedAt = false; }
        $this->pdo->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');

            $updSql = $hasUpdatedAt
                ? "UPDATE settings SET {$col} = :v, updated_at = :u WHERE setting_key = :k"
                : "UPDATE settings SET {$col} = :v WHERE setting_key = :k";
            $insSql = $hasUpdatedAt
                ? "INSERT INTO settings (setting_key, {$col}, updated_at) VALUES (:k, :v, :u)"
                : "INSERT INTO settings (setting_key, {$col}) VALUES (:k, :v)";

            $upd = $this->pdo->prepare($updSql);
            $ins = $this->pdo->prepare($insSql);

            foreach ($pairs as $k => $v) {
                $val = is_array($v)
                    ? json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)
                    : (string)$v;

                $p = [':k' => (string)$k, ':v' => $val];
                if ($hasUpdatedAt) { $p[':u'] = $now; }
                $upd->execute($p);
                if ((int)$upd->rowCount() === 0) {
                    $ins->execute($p);
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    
    
    
    public static function get(string $key, $default = null)
    {
        return (new self(DB::pdo()))->getValue($key, $default);
    }

    public static function set(string $key, $value): void
    {
        (new self(DB::pdo()))->setValue($key, $value);
    }

    
    public static function many(array $pairs): void
    {
        (new self(DB::pdo()))->setMany($pairs);
    }
}
