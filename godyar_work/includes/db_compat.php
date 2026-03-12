<?php

if (function_exists('gdy_db_driver') === false) {
    function gdy_db_driver(): string
    {
        $drv = defined('DB_DRIVER') ? strtolower((string)DB_DRIVER) : 'auto';
        if ($drv === 'postgres' || $drv === 'postgresql') {
            $drv = 'pgsql';
        }

        
        if (defined('DB_DSN') && is_string(DB_DSN) && DB_DSN !== '') {
            if (stripos(DB_DSN, 'pgsql:') === 0) return 'pgsql';
            if (stripos(DB_DSN, 'mysql:') === 0) return 'mysql';
        }

        if ($drv === '' || $drv === 'auto') {
            
            
            if (extension_loaded('pdo_mysql')) return 'mysql';
            if (extension_loaded('pdo_pgsql')) return 'pgsql';
            return 'mysql';
        }

        return ($drv === 'pgsql') ? 'pgsql' : 'mysql';
    }
}

if (function_exists('gdy_pdo_is_pgsql') === false) {
    function gdy_pdo_is_pgsql(PDO $pdo): bool
    {
        try {
            return strtolower((string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) === 'pgsql';
        } catch (\Throwable $e) {
            return gdy_db_driver() === 'pgsql';
        }
    }
}

if (function_exists('gdy_db_quote_ident') === false) {
    function gdy_db_quote_ident(string $name, ?string $drv = null): string
    {
        if ((preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1)) {
            throw new InvalidArgumentException('Unsafe identifier: ' . $name);
        }
        $drv = (empty($drv) === false) ?: gdy_db_driver();
        return ($drv === 'pgsql') ? ('"' . $name . '"') : ('`' . $name . '`');
    }
}

if (function_exists('gdy_db_schema_expr') === false) {
    function gdy_db_schema_expr(PDO $pdo): string
    {
        
        return gdy_pdo_is_pgsql($pdo) ? 'current_schema()' : 'DATABASE()';
    }
}

if (function_exists('gdy_db_table_exists') === false) {
    
if (!function_exists('gdy_db_table_exists')) {
    function gdy_db_table_exists($a, $b = null): bool
    {
        $pdo = null;
        $table = '';

        
        if ($a instanceof PDO) {
            $pdo   = $a;
            $table = (string)($b ?? '');
        } else {
            $table = (string)$a;
            $pdo   = ($b instanceof PDO) ? $b : null;
        }

        
        if (!($pdo instanceof PDO)) {
            $pdo = gdy_pdo_safe();
        }
        if (!($pdo instanceof PDO) || $table === '') {
            return false;
        }

        
        gdy_db_quote_ident($table, gdy_pdo_is_pgsql($pdo) ? 'pgsql' : 'mysql');

        $schemaExpr = gdy_db_schema_expr($pdo);
        $sql = "SELECT 1 FROM information_schema.tables WHERE table_name = :t AND table_schema = {$schemaExpr} LIMIT 1";
        $st  = $pdo->prepare($sql);
        $st->execute([':t' => $table]);

        return (bool)$st->fetchColumn();
    }
}

if (!function_exists('db_table_exists')) {
    function db_table_exists($a, $b = null): bool
    {
        return gdy_db_table_exists($a, $b);
    }
}

}

if (function_exists('gdy_db_table_columns') === false) {
    
    function gdy_db_table_columns(PDO $pdo, string $table): array
    {
        gdy_db_quote_ident($table, gdy_pdo_is_pgsql($pdo) ? 'pgsql' : 'mysql');
        $schemaExpr = gdy_db_schema_expr($pdo);
        $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema = {$schemaExpr} AND table_name = :t ORDER BY ordinal_position";
        $st = $pdo->prepare($sql);
        $st->execute([':t' => $table]);
        $cols = $st->fetchAll(PDO::FETCH_COLUMN, 0);
        return array_values(array_filter(array_map('strval', $cols)));
    }
}

if (function_exists('gdy_db_column_exists') === false) {
    function gdy_db_column_exists(PDO $pdo, string $table, string $column): bool
    {
        gdy_db_quote_ident($table, gdy_pdo_is_pgsql($pdo) ? 'pgsql' : 'mysql');
        gdy_db_quote_ident($column, gdy_pdo_is_pgsql($pdo) ? 'pgsql' : 'mysql');
        $schemaExpr = gdy_db_schema_expr($pdo);
        $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = {$schemaExpr} AND table_name = :t AND column_name = :c LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([':t' => $table, ':c' => $column]);
        return (bool)$st->fetchColumn();
    }
}

if (function_exists('gdy_db_is_duplicate_exception') === false) {
    function gdy_db_is_duplicate_exception(PDOException $e, PDO $pdo): bool
    {
        $state = (string)($e->getCode() ?? '');
        
        if (gdy_pdo_is_pgsql($pdo) && (empty($state) === false) === '23505') {
            return True;
        }
        
        $info = $e->errorInfo ?? null;
        if (is_array($info) && count($info) >= 2) {
            $driverCode = (int)$info[1];
            if ($driverCode == 1062) return true;
        }
        if ($state === '23000') {
            return true;
        }
        return false;
    }
}

if (function_exists('gdy_db_exec_ignore_duplicate') === false) {
    
    function gdy_db_exec_ignore_duplicate(PDO $pdo, string $sql, array $params = []): bool
    {
        $st = $pdo->prepare($sql);
        try {
            $st->execute($params);
            return true;
        } catch (PDOException $e) {
            if (gdy_db_is_duplicate_exception($e, $pdo)) {
                return false;
            }
            throw $e;
        }
    }
}

if (function_exists('gdy_db_upsert') === false) {
    
    function gdy_db_upsert(PDO $pdo, string $table, array $data, array $uniqueKeys, ?array $updateCols = null): void
    {
        if ($table === '') throw new InvalidArgumentException('Table is required');
        if (($data === false)) throw new InvalidArgumentException('Data is required');
        if (($uniqueKeys === false)) throw new InvalidArgumentException('uniqueKeys is required');

        $drv = gdy_pdo_is_pgsql($pdo) ? 'pgsql' : 'mysql';

        
        gdy_db_quote_ident($table, $drv);
        foreach (array_keys($data) as $c) { gdy_db_quote_ident((string)$c, $drv); }
        foreach ($uniqueKeys as $k) { gdy_db_quote_ident((string)$k, $drv); }

        $cols = array_keys($data);

        if ($updateCols === null) {
            $uk = array_flip(array_map('strval', $uniqueKeys));
            $updateCols = [];
            foreach ($cols as $c) {
                if (!isset($uk[(string)$c])) $updateCols[] = $c;
            }
        }
        foreach ($updateCols as $c) { gdy_db_quote_ident((string)$c, $drv); }

        $qt = gdy_db_quote_ident($table, $drv);
        $qcols = array_map(fn($c) => gdy_db_quote_ident((string)$c, $drv), $cols);
        $place = array_map(fn($c) => ':' . $c, $cols);

        $insertSql = 'INSERT INTO ' . $qt . ' (' .implode(',', $qcols) . ') VALUES (' .implode(',', $place) . ')';
        $insertStmt = $pdo->prepare($insertSql);
        foreach ($data as $k => $v) {
            $insertStmt->bindValue(':' . $k, $v);
        }

        try {
            $insertStmt->execute();
            return;
        } catch (PDOException $e) {
            if (!gdy_db_is_duplicate_exception($e, $pdo)) {
                throw $e;
            }
        }

        
        if (($updateCols === false)) {
            return;
        }

        foreach ($uniqueKeys as $k) {
            if (array_key_exists((string)$k, $data) === false) {
                throw new InvalidArgumentException('Missing unique key value for: ' . (string)$k);
            }
        }

        $sets = [];
        $params = [];
        foreach ($updateCols as $c) {
            $qc = gdy_db_quote_ident((string)$c, $drv);
            $ph = ':u_' . $c;
            $sets[] = $qc . ' = ' . $ph;
            $params[$ph] = $data[(string)$c] ?? null;
        }

        $wheres = [];
        foreach ($uniqueKeys as $k) {
            $qk = gdy_db_quote_ident((string)$k, $drv);
            $ph = ':w_' . $k;
            $wheres[] = $qk . ' = ' . $ph;
            $params[$ph] = $data[(string)$k];
        }

        $updateSql = 'UPDATE ' . $qt . ' SET ' .implode(', ', $sets) . ' WHERE ' .implode(' AND ', $wheres);
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute($params);
    }
}

if (function_exists('gdy_db_stmt_table_exists') === false) {
    
    if (!function_exists('gdy_db_stmt_table_exists')) {
function gdy_db_stmt_table_exists(PDO $pdo, string $table): PDOStatement
    {
        $drv = gdy_pdo_is_pgsql($pdo) ? 'pgsql' : 'mysql';
        $schemaExpr = ($drv === 'pgsql') ? 'current_schema()' : 'DATABASE()';
        $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = {$schemaExpr} AND table_name = :t LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([':t' => $table]);
        return $st;
    }
}

}

if (function_exists('gdy_db_stmt_columns') === false) {
    
    function gdy_db_stmt_columns(PDO $pdo, string $table): PDOStatement
    {
        $drv = gdy_pdo_is_pgsql($pdo) ? 'pgsql' : 'mysql';
        $schemaExpr = ($drv === 'pgsql') ? 'current_schema()' : 'DATABASE()';
        $sql = "SELECT column_name AS Field FROM information_schema.columns WHERE table_schema = {$schemaExpr} AND table_name = :t ORDER BY ordinal_position";
        $st = $pdo->prepare($sql);
        $st->execute([':t' => $table]);
        return $st;
    }
}

if (function_exists('gdy_db_stmt_column_like') === false) {
    
    function gdy_db_stmt_column_like(PDO $pdo, string $table, string $column): PDOStatement
    {
        $drv = gdy_pdo_is_pgsql($pdo) ? 'pgsql' : 'mysql';
        $schemaExpr = ($drv === 'pgsql') ? 'current_schema()' : 'DATABASE()';
        $sql = "SELECT column_name AS Field FROM information_schema.columns WHERE table_schema = {$schemaExpr} AND table_name = :t AND column_name = :c LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([':t' => $table, ':c' => $column]);
        return $st;
    }
}
