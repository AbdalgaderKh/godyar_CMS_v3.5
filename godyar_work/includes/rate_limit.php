<?php

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (!function_exists('gdy_mkdir')) {
    function gdy_mkdir(string $path, int $mode = 0755, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }
        return @mkdir($path, $mode, $recursive);
    }
}

if (!function_exists('gdy_file_get_contents')) {
    function gdy_file_get_contents(string $path): string|false
    {
        return @file_get_contents($path);
    }
}

if (!function_exists('gdy_file_put_contents')) {
    function gdy_file_put_contents(string $path, string $data, int $flags = 0): int|false
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return @file_put_contents($path, $data, $flags);
    }
}

function gody_rate_limit(string $bucket, int $max = 5, int $windowSeconds = 600): bool
{
    $dir = rtrim(ROOT_PATH, '/\\') . '/storage/ratelimit';
    if (!is_dir($dir)) {
        gdy_mkdir($dir, 0755, true);
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = hash('sha256', $bucket . '|' . $ip);
    $file = $dir . '/' . $key . '.json';

    $now = time();
    $data = ['count' => 0, 'reset' => $now + $windowSeconds];

    if (is_file($file)) {
        $raw = gdy_file_get_contents($file);
        if (is_string($raw) && $raw !== '') {
            $j = json_decode($raw, true);
            if (is_array($j) && isset($j['count'], $j['reset'])) {
                $data['count'] = (int)$j['count'];
                $data['reset'] = (int)$j['reset'];
            }
        }
    }

    if ($now > $data['reset']) {
        $data = ['count' => 0, 'reset' => $now + $windowSeconds];
    }

    $data['count'] += 1;
    gdy_file_put_contents(
        $file,
        (string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );

    $ok = $data['count'] <= $max;
    if (!$ok && function_exists('gdy_security_log')) {
        gdy_security_log('rate_limited', [
            'bucket' => $bucket,
            'max' => $max,
            'window' => $windowSeconds,
            'reset' => $data['reset'],
        ]);
    }

    return $ok;
}

function gody_rate_limit_retry_after(string $bucket): int
{
    $dir = rtrim(ROOT_PATH, '/\\') . '/storage/ratelimit';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = hash('sha256', $bucket . '|' . $ip);
    $file = $dir . '/' . $key . '.json';

    if (!is_file($file)) {
        return 0;
    }

    $raw = gdy_file_get_contents($file);
    $j = json_decode($raw ?: '', true);
    $reset = isset($j['reset']) ? (int)$j['reset'] : 0;
    $now = time();

    return max(0, $reset - $now);
}