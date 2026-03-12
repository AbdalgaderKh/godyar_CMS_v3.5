<?php

final class Security
{
    
    public static function cleanInput(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $s = (string)$value;
        
        $s = str_replace("\0", '', $s);
        
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? $s;
        return trim($s);
    }

    
	
	public static function logSecurityEvent(string $event, mixed $context = []): void
    {
		
		if (!is_array($context)) {
			$context = ['value' => $context];
		}
        try {
            $root = defined('ROOT_PATH') ? rtrim((string)ROOT_PATH, '/\\') : dirname(__DIR__, 2);

            
            $logDir = $root . '/storage/logs';

            
            $priv = rtrim(dirname($root), '/\\') . '/godyar_private';
            if (is_dir($priv)) {
                $logDir = $priv . '/logs';
            }

            if (!is_dir($logDir)) {
                if (function_exists('gdy_mkdir')) {
                    @gdy_mkdir($logDir, 0775, true);
                } else {
                    @mkdir($logDir, 0775, true);
                }
            }

            $file = rtrim($logDir, '/\\') . '/security.log';
            $row = [
                'ts' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'uid' => $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null),
                'event' => $event,
                'ctx' => $context,
            ];

            @file_put_contents(
                $file,
                json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
                FILE_APPEND | LOCK_EX
            );
        } catch (Throwable) {
            
        }
    }
}
