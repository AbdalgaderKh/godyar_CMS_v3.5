<?php

declare(strict_types=1);

namespace GodyarV4\Support;

use PDO;
use Throwable;

final class LegacyDbBridge
{
    private static ?PDO $pdo = null;
    private static ?bool $bootstrapped = null;
    /** @var array<string,bool> */
    private static array $tableCache = [];
    /** @var array<string,array<string,bool>> */
    private static array $columnCache = [];

    public static function pdo(): ?PDO
    {
        if (self::$bootstrapped === true) {
            return self::$pdo;
        }
        self::$bootstrapped = true;

        $root = godyar_v4_project_root();

        $dbClass = $root . '/includes/classes/DB.php';
        $envFile = $root . '/includes/env.php';

        try {
            if (is_file($envFile)) {
                require_once $envFile;
            }
            if (is_file($dbClass)) {
                require_once $dbClass;
            }
            if (class_exists('Godyar\\DB') && method_exists('Godyar\\DB', 'pdoOrNull')) {
                /** @var ?PDO $pdo */
                $pdo = \Godyar\DB::pdoOrNull();
                self::$pdo = $pdo;
            }
        } catch (Throwable) {
            self::$pdo = null;
        }

        return self::$pdo;
    }

    public static function tableExists(string $table): bool
    {
        $key = strtolower($table);
        if (array_key_exists($key, self::$tableCache)) {
            return self::$tableCache[$key];
        }

        $pdo = self::pdo();
        if (!$pdo) {
            return self::$tableCache[$key] = false;
        }

        try {
            $st = $pdo->prepare('SHOW TABLES LIKE :table');
            $st->execute(['table' => $table]);
            return self::$tableCache[$key] = (bool) $st->fetchColumn();
        } catch (Throwable) {
            return self::$tableCache[$key] = false;
        }
    }

    public static function columnExists(string $table, string $column): bool
    {
        $tableKey = strtolower($table);
        $colKey = strtolower($column);
        if (isset(self::$columnCache[$tableKey][$colKey])) {
            return self::$columnCache[$tableKey][$colKey];
        }

        $pdo = self::pdo();
        if (!$pdo || !self::tableExists($table)) {
            return self::$columnCache[$tableKey][$colKey] = false;
        }

        try {
            $st = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column");
            $st->execute(['column' => $column]);
            return self::$columnCache[$tableKey][$colKey] = (bool) $st->fetchColumn();
        } catch (Throwable) {
            return self::$columnCache[$tableKey][$colKey] = false;
        }
    }

    /** @param array<int|string,mixed> $params */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $pdo = self::pdo();
        if (!$pdo) {
            return null;
        }

        try {
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $row : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array<int|string,mixed> $params
     *  @return array<int,array<string,mixed>>
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $pdo = self::pdo();
        if (!$pdo) {
            return [];
        }

        try {
            $st = $pdo->prepare($sql);
            $st->execute($params);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }
}
