<?php

if (!function_exists('gdy__starts_with')) {
    function gdy__starts_with($haystack, $needle) {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('gdy__read_env_file')) {
    function gdy__read_env_file($envFile) {
        $vars = array();
        if (!is_file($envFile)) return $vars;
        $lines = @file($envFile, FILE_IGNORE_NEW_LINES);
        if (!$lines) return $vars;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || gdy__starts_with($line, '#')) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) continue;
            $k = trim($parts[0]);
            $v = trim($parts[1]);
            $v = trim($v, "\"'");
            if ($k !== '') $vars[$k] = $v;
        }
        return $vars;
    }
}

if (!function_exists('gdy_env_get')) {
    function gdy_env_get($key, $default = '') {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return (string)$_ENV[$key];
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return (string)$_SERVER[$key];
        $v = getenv($key);
        if ($v !== false && $v !== '') return (string)$v;

        $envFile = dirname(__DIR__) . '/.env';
        $vars = gdy__read_env_file($envFile);
        if (isset($vars[$key]) && $vars[$key] !== '') return (string)$vars[$key];
        return (string)$default;
    }
}

if (!function_exists('gdy_sql_split_statements')) {
    function gdy_sql_split_statements($sql) {
        
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
        $len = strlen($sql);
        $stmts = array();
        $buf = '';
        $inS = false; 
        $inD = false; 
        $inB = false; 
        $esc = false;

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];

            if ($esc) {
                $buf .= $ch;
                $esc = false;
                continue;
            }

            if (($inS || $inD) && $ch === '\\') {
                $buf .= $ch;
                $esc = true;
                continue;
            }

            if (!$inD && !$inB && $ch === "'") { $inS = !$inS; $buf .= $ch; continue; }
            if (!$inS && !$inB && $ch === '"') { $inD = !$inD; $buf .= $ch; continue; }
            if (!$inS && !$inD && $ch === '`') { $inB = !$inB; $buf .= $ch; continue; }

            if (!$inS && !$inD && !$inB && $ch === ';') {
                $stmt = trim($buf);
                $buf = '';
                if ($stmt !== '') $stmts[] = $stmt;
                continue;
            }

            $buf .= $ch;
        }

        $tail = trim($buf);
        if ($tail !== '') $stmts[] = $tail;

        
        $out = array();
        foreach ($stmts as $s) {
            $lines = preg_split('/\r?\n/', $s);
            $clean = array();
            foreach ($lines as $ln) {
                $lnTrim = ltrim($ln);
                if ($lnTrim === '') continue;
                if (gdy__starts_with($lnTrim, '--')) continue;
                $clean[] = $ln;
            }
            $final = trim(implode("\n", $clean));
            if ($final !== '') $out[] = $final;
        }
        return $out;
    }
}

if (!function_exists('gdy_migrations_table_ensure')) {
    function gdy_migrations_table_ensure($pdo) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (\n"
            . "  id VARCHAR(191) PRIMARY KEY,\n"
            . "  ran_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP\n"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

if (!function_exists('gdy_migrations_get_ran')) {
    function gdy_migrations_get_ran($pdo) {
        $ran = array();
        try {
            $stmt = $pdo->query('SELECT id FROM migrations');
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!empty($row['id'])) $ran[$row['id']] = true;
                }
            }
        } catch (Exception $e) {
            
        }
        return $ran;
    }
}

if (!function_exists('gdy_migrations_mark_ran')) {
    function gdy_migrations_mark_ran($pdo, $id) {
        $stmt = $pdo->prepare('INSERT INTO migrations (id) VALUES (?)');
        $stmt->execute(array($id));
    }
}

if (!function_exists('gdy_run_migrations')) {
    
    function gdy_run_migrations($pdo, $migrationsDir) {
        if (!$pdo) {
            return array(false, "PDO connection is not available\n");
        }
        if (!is_dir($migrationsDir)) {
            return array(false, "Migrations directory not found: {$migrationsDir}\n");
        }

        gdy_migrations_table_ensure($pdo);
        $ran = gdy_migrations_get_ran($pdo);

        $files = glob(rtrim($migrationsDir, '/\\') . '/*.{sql,php}', GLOB_BRACE);
        if (!$files) $files = array();
        sort($files);

        $out = '';
        $applied = 0;

        foreach ($files as $file) {
            $base = basename($file);
            $id = $base;
            if (isset($ran[$id])) {
                $out .= "SKIP  {$id}\n";
                continue;
            }

            $out .= "RUN   {$id}\n";
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            try {
                if ($ext === 'sql') {
                    $sql = file_get_contents($file);
                    $stmts = gdy_sql_split_statements($sql);
                    foreach ($stmts as $stmtSql) {
                        $pdo->exec($stmtSql);
                    }
                } elseif ($ext === 'php') {
                    $spec = include $file;
                    if (!is_array($spec)) {
                        throw new Exception('Migration php must return array');
                    }
                    $up = isset($spec['up']) ? $spec['up'] : null;
                    if (is_callable($up)) {
                        call_user_func($up, $pdo);
                    } elseif (is_string($up) && trim($up) !== '') {
                        $stmts = gdy_sql_split_statements($up);
                        foreach ($stmts as $stmtSql) {
                            $pdo->exec($stmtSql);
                        }
                    } else {
                        throw new Exception('Migration has no up');
                    }
                }

                gdy_migrations_mark_ran($pdo, $id);
                $applied++;
                $out .= "OK    {$id}\n";
            } catch (Exception $e) {
                $out .= "FAIL  {$id}: {$e->getMessage()}\n";
                return array(false, $out);
            }
        }

        $out .= "\nApplied: {$applied}\n";
        return array(true, $out);
    }
}
