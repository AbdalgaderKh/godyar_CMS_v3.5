<?php
namespace Godyar\Services;

use PDO;

final class WebPushService
{
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    
    public function sendBroadcast(array $payload, int $ttlSeconds = 3600, bool $testOnly = false): array
    {
        
        if (!$this->pdo instanceof PDO) {
            return ['ok' => false, 'sent' => 0, 'failed' => 0, 'message' => 'PDO unavailable'];
        }

        
        if (function_exists('gdy_db_stmt_table_exists')) {
            try {
                if (!gdy_db_stmt_table_exists($this->pdo, 'push_subscriptions')) {
                    return ['ok' => false, 'sent' => 0, 'failed' => 0, 'message' => 'push_subscriptions table missing'];
                }
            } catch (\Throwable $e) {
                
            }
        }

        
        
        if ($testOnly) {
            return ['ok' => true, 'sent' => 0, 'failed' => 0, 'message' => 'Test mode: no delivery'];
        }

        
        try {
            if (function_exists('gdy_db_stmt_table_exists') && gdy_db_stmt_table_exists($this->pdo, 'push_broadcasts')) {
                $stmt = $this->pdo->prepare('INSERT INTO push_broadcasts (payload_json, ttl, created_at) VALUES (:p,:t,NOW())');
                $stmt->execute([
                    ':p' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ':t' => $ttlSeconds,
                ]);
            }
        } catch (\Throwable $e) {
            
        }

        return ['ok' => true, 'sent' => 0, 'failed' => 0, 'message' => 'Broadcast queued (stub)'];
    }
}
