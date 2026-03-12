<?php

if (!defined('GDY_BOOTSTRAPPED')) {
    $boot = __DIR__ . '/bootstrap.php';
    if (is_file($boot)) {
        require_once $boot;
    }
}

use Godyar\DB;

if (!function_exists('gdy_pdo_safe')) {
    
    function gdy_pdo_safe(): ?\PDO
    {
        try {
            return DB::pdo();
        } catch (\Throwable $e) {
            error_log('[Godyar DB] PDO unavailable: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('db')) {
    function db(): ?\PDO
    {
        return gdy_pdo_safe();
    }
}

if (!function_exists('gdy_pdo')) {
    
    function gdy_pdo(): \PDO
    {
        return DB::pdo();
    }
}

if (!function_exists('gdy_register_global_pdo')) {
    
    function gdy_register_global_pdo(): void
    {
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) {
            return;
        }

        $raw = getenv('LEGACY_GLOBAL_PDO');
        $enabled = true;
        if ($raw !== false && $raw !== null && $raw !== '') {
            $enabled = !in_array(strtolower((string)$raw), ['0', 'false', 'off', 'no'], true);
        }

        if (!$enabled) {
            return;
        }

        $pdo = gdy_pdo_safe();
        if (!$pdo) {
            return;
        }

        $GLOBALS['pdo'] = $pdo;

        if (empty($GLOBALS['__godyar_warned_global_pdo'])) {
            $GLOBALS['__godyar_warned_global_pdo'] = true;
            error_log('[DEPRECATED] $GLOBALS[\'pdo\'] is enabled for backward compatibility. Prefer Godyar\\DB::pdo() / gdy_pdo().');
        }
    }
}

if (!function_exists('gdy_db_quote_ident')) {
    
    function gdy_db_quote_ident(string $ident): string
    {
        return DB::quoteIdent($ident);
    }
}

if (!function_exists('gdy_db_schema_allowed')) {
    function gdy_db_schema_allowed(): bool
    {
        $raw = getenv('ALLOW_SCHEMA_CHANGES');
        if ($raw === false || $raw === null || $raw === '') {
            return false;
        }
        return !in_array(strtolower((string) $raw), ['0', 'false', 'off', 'no'], true);
    }
}

if (!function_exists('gdy_db_driver')) {
    function gdy_db_driver(?\PDO $pdo = null): string
    {
        try {
            $pdo = $pdo ?: DB::pdo();
            return (string) $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (Exception) {
            return 'unknown';
        }
    }
}

if (!function_exists('gdy_db_current_database')) {
    function gdy_db_current_database(?\PDO $pdo = null): string
    {
        $pdo = $pdo ?: DB::pdo();
        $drv = gdy_db_driver($pdo);
        if ($drv === 'pgsql') {
            return (string) $pdo->query('select current_database()')->fetchColumn();
        }
        if ($drv === 'sqlite') {
            return 'main';
        }
        
        return (string) $pdo->query('select database()')->fetchColumn();
    }
}

if (!function_exists('gdy_db_exec_ddl')) {
    
    function gdy_db_exec_ddl(string $sql, array $params = [], ?\PDO $pdo = null): void
    {
        if (!gdy_db_schema_allowed()) {
            throw new \RuntimeException('Schema changes are disabled (set ALLOW_SCHEMA_CHANGES=1 to enable)');
        }
        $pdo = $pdo ?: DB::pdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
}

if (!function_exists('gdy_db_table_exists')) {
    function gdy_db_table_exists(string $table, ?\PDO $pdo = null): bool
    {
        $pdo = $pdo ?: DB::pdo();
        $drv = gdy_db_driver($pdo);
        $table = trim($table);
        
        DB::quoteIdent($table);

        if ($drv === 'pgsql') {
            $sql = "select 1 from information_schema.tables where table_schema = current_schema() and table_name = :t limit 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':t' => $table]);
            return (bool) $stmt->fetchColumn();
        }

        if ($drv === 'sqlite') {
            $stmt = $pdo->prepare("select 1 from sqlite_master where type='table' and name=:t limit 1");
            $stmt->execute([':t' => $table]);
            return (bool) $stmt->fetchColumn();
        }

        
        $db = gdy_db_current_database($pdo);
        $sql = "select 1 from information_schema.tables where table_schema = :db and table_name = :t limit 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':db' => $db, ':t' => $table]);
        return (bool) $stmt->fetchColumn();
    }
}

if (!function_exists('db_table_exists')) {
    
    function db_table_exists($a, $b = null): bool
    {
        try {
            if ($a instanceof \PDO) {
                return gdy_db_table_exists((string) $b, $a);
            }
            return gdy_db_table_exists((string) $a, $b instanceof \PDO ? $b : null);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('gdy_db_column_info')) {
    
    function gdy_db_column_info(string $table, string $column, ?\PDO $pdo = null): ?array
    {
        $pdo = $pdo ?: DB::pdo();
        $drv = gdy_db_driver($pdo);

        DB::quoteIdent($table);
        DB::quoteIdent($column);

        if ($drv === 'pgsql') {
            $sql = "select column_name, data_type, is_nullable, character_maximum_length, numeric_precision, numeric_scale, column_default
                    from information_schema .columns
                    where table_schema = current_schema() and table_name = :t and column_name = :c
                    limit 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':t' => $table, ':c' => $column]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        }

        if ($drv === 'sqlite') {
            $q = 'PRAGMA table_info(' .DB::quoteIdent($table) . ')';
            $rows = $pdo->query($q)->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $r) {
                if ((string) ($r['name'] ?? '') === $column) {
                    return $r;
                }
            }
            return null;
        }

        
        $db = gdy_db_current_database($pdo);
        $sql = "select column_name, column_type, is_nullable, column_default, extra, collation_name
                from information_schema .columns
                where table_schema = :db and table_name = :t and column_name = :c
                limit 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':db' => $db, ':t' => $table, ':c' => $column]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('gdy_db_column_exists')) {
    function gdy_db_column_exists(string $table, string $column, ?\PDO $pdo = null): bool
    {
        return gdy_db_column_info($table, $column, $pdo) !== null;
    }
}

if (!function_exists('gdy_db_index_exists')) {
    function gdy_db_index_exists(string $table, string $indexName, ?\PDO $pdo = null): bool
    {
        $pdo = $pdo ?: DB::pdo();
        $drv = gdy_db_driver($pdo);

        DB::quoteIdent($table);
        DB::quoteIdent($indexName);

        if ($drv === 'pgsql') {
            
            $sql = "select 1 from pg_indexes where schemaname = current_schema() and tablename = :t and indexname = :i limit 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':t' => $table, ':i' => $indexName]);
            return (bool) $stmt->fetchColumn();
        }

        if ($drv === 'sqlite') {
            $stmt = $pdo->prepare("select 1 from sqlite_master where type='index' and tbl_name=:t and name=:i limit 1");
            $stmt->execute([':t' => $table, ':i' => $indexName]);
            return (bool) $stmt->fetchColumn();
        }

        
        $db = gdy_db_current_database($pdo);
        $sql = "select 1 from information_schema.statistics where table_schema = :db and table_name = :t and index_name = :i limit 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':db' => $db, ':t' => $table, ':i' => $indexName]);
        return (bool) $stmt->fetchColumn();
    }
}

if (!function_exists('gdy_db_ensure_table')) {
    
    function gdy_db_ensure_table(string $table, string $createSql, ?\PDO $pdo = null): bool
    {
        $pdo = $pdo ?: DB::pdo();
        DB::quoteIdent($table);
        if (gdy_db_table_exists($table, $pdo)) {
            return false;
        }
        
        gdy_db_exec_ddl($createSql, [], $pdo);
        return true;
    }
}

if (!function_exists('gdy_db_ensure_column')) {
    
    function gdy_db_ensure_column(string $table, string $column, string $definition, ?string $after = null, ?\PDO $pdo = null): bool
    {
        $pdo = $pdo ?: DB::pdo();
        $drv = gdy_db_driver($pdo);

        DB::quoteIdent($table);
        DB::quoteIdent($column);
        if ($after !== null && $after !== '') {
            DB::quoteIdent($after);
        }

        if (gdy_db_column_exists($table, $column, $pdo)) {
            return false;
        }

        $qt = DB::quoteIdent($table);
        $qc = DB::quoteIdent($column);

        if ($drv === 'pgsql') {
            $sql = "alter table {$qt} add column {$qc} {$definition}";
            gdy_db_exec_ddl($sql, [], $pdo);
            return true;
        }

        if ($drv === 'sqlite') {
            
            $sql = "alter table {$qt} add column {$qc} {$definition}";
            gdy_db_exec_ddl($sql, [], $pdo);
            return true;
        }

        
        $sql = "alter table {$qt} add column {$qc} {$definition}";
        if ($after !== null && $after !== '') {
            $qa = DB::quoteIdent($after);
            $sql .= " after {$qa}";
        }
        gdy_db_exec_ddl($sql, [], $pdo);
        return true;
    }
}

if (!function_exists('gdy_db_add_index')) {
    
    function gdy_db_add_index(string $table, string $indexName, array $columns, bool $unique = false, ?\PDO $pdo = null): bool
    {
        $pdo = $pdo ?: DB::pdo();
        $drv = gdy_db_driver($pdo);

        DB::quoteIdent($table);
        DB::quoteIdent($indexName);
        if (empty($columns)) {
            throw new \InvalidArgumentException('Index columns cannot be empty');
        }
        foreach ($columns as $c) {
            DB::quoteIdent((string) $c);
        }

        if (gdy_db_index_exists($table, $indexName, $pdo)) {
            return false;
        }

        $qt = DB::quoteIdent($table);
        $qi = DB::quoteIdent($indexName);
        $cols = implode(', ', array_map(static fn ($c) => DB::quoteIdent((string) $c), $columns));

        if ($drv === 'pgsql') {
            $uq = $unique ? 'unique ' : '';
            $sql = "create {$uq}index {$qi} on {$qt} ({$cols})";
            gdy_db_exec_ddl($sql, [], $pdo);
            return true;
        }

        if ($drv === 'sqlite') {
            $uq = $unique ? 'unique ' : '';
            $sql = "create {$uq}index {$qi} on {$qt} ({$cols})";
            gdy_db_exec_ddl($sql, [], $pdo);
            return true;
        }

        
        $uq = $unique ? 'unique ' : '';
        
        
        $sql = "alter table {$qt} add {$uq}index {$qi} ({$cols})";
        gdy_db_exec_ddl($sql, [], $pdo);
        return true;
    }
}

if (!function_exists('gdy_db_migrations_bootstrap')) {
    
    function gdy_db_migrations_bootstrap(?\PDO $pdo = null): void
    {
        $pdo = $pdo ?: DB::pdo();
        $drv = gdy_db_driver($pdo);
        if (gdy_db_table_exists('godyar_migrations', $pdo)) {
            return;
        }

        if ($drv === 'pgsql') {
            $sql = "create table godyar_migrations (id bigserial primary key, name varchar(190) not null unique, applied_at timestamptz not null default now())";
            gdy_db_exec_ddl($sql, [], $pdo);
            return;
        }

        if ($drv === 'sqlite') {
            $sql = "create table godyar_migrations (id integer primary key autoincrement, name text not null unique, applied_at text not null)";
            gdy_db_exec_ddl($sql, [], $pdo);
            return;
        }

        
        $sql = "create table godyar_migrations (id bigint unsigned not null auto_increment primary key, name varchar(190) not null, applied_at timestamp not null default current_timestamp, unique key u_mig_name (name)) engine=InnoDB default charset=utf8mb4";
        gdy_db_exec_ddl($sql, [], $pdo);
    }
}

if (!function_exists('gdy_db_migration_applied')) {
    function gdy_db_migration_applied(string $name, ?\PDO $pdo = null): bool
    {
        $pdo = $pdo ?: DB::pdo();
        gdy_db_migrations_bootstrap($pdo);
        $stmt = $pdo->prepare('select 1 from godyar_migrations where name = :n limit 1');
        $stmt->execute([':n' => $name]);
        return (bool) $stmt->fetchColumn();
    }
}

if (!function_exists('gdy_db_apply_migration')) {
    
    function gdy_db_apply_migration(string $name, callable $fn, ?\PDO $pdo = null): bool
    {
        $pdo = $pdo ?: DB::pdo();
        gdy_db_migrations_bootstrap($pdo);

        if (gdy_db_migration_applied($name, $pdo)) {
            return false;
        }

        if (!gdy_db_schema_allowed()) {
            throw new \RuntimeException('Schema changes are disabled (set ALLOW_SCHEMA_CHANGES=1 to enable)');
        }

        $pdo->beginTransaction();
        try {
            $fn($pdo);
            $stmt = $pdo->prepare('insert into godyar_migrations (name) values (:n)');
            $stmt->execute([':n' => $name]);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}

if (!function_exists('gdy_table_exists')) {
    function gdy_table_exists(PDO $pdo, string $table): bool {
        try {
            $st = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
            $st->execute([':t' => $table]);
            return (bool)$st->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('gdy_column_exists')) {
    function gdy_column_exists(PDO $pdo, string $table, string $column): bool {
        try {
            $st = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1");
            $st->execute([':t' => $table, ':c' => $column]);
            return (bool)$st->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('gdy_news_owner_column')) {
    
    function gdy_news_owner_column(PDO $pdo): ?string {
        if (gdy_column_exists($pdo, 'news', 'author_id')) return 'author_id';
        if (gdy_column_exists($pdo, 'news', 'user_id')) return 'user_id';
        return null;
    }
}

if (!function_exists('gdy_ensure_opinion_authors_table')) {
    function gdy_ensure_opinion_authors_table(PDO $pdo): void {
        if (gdy_table_exists($pdo, 'opinion_authors')) return;
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS opinion_authors (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    name VARCHAR(190) NOT NULL,
                    slug VARCHAR(190) NOT NULL,
                    avatar VARCHAR(255) NULL,
                    specialization VARCHAR(190) NULL,
                    bio TEXT NULL,
                    page_title VARCHAR(255) NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    display_order INT NOT NULL DEFAULT 0,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_opinion_authors_slug (slug),
                    KEY idx_is_active (is_active),
                    KEY idx_display_order (display_order)
                ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci
            ");
        } catch (\Throwable $e) {
            
        }
    }
}

if (!function_exists('gdy_ensure_news_author_id')) {
    
    function gdy_ensure_news_author_id(PDO $pdo): void {
        if (gdy_column_exists($pdo, 'news', 'author_id')) return;
        try {
            $pdo->exec("ALTER TABLE news ADD COLUMN author_id INT NULL");
        } catch (\Throwable $e) {
            
        }
    }
}

if (!function_exists('getDB')) {
    function getDB(): \PDO {
        return \Godyar\DB::pdo();
    }
}

