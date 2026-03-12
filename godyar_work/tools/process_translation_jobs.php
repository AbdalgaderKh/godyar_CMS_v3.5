<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/smart_translation_engine.php';

$key = isset($_GET['key']) ? (string)$_GET['key'] : '';
$expected = (string) gdy_env_value('TRANSLATION_CRON_KEY', '');

if ($expected === '' || !hash_equals($expected, $key)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = gdy_translation_db();
    $stmt = $pdo->query("SELECT id FROM translation_jobs WHERE status='queued' ORDER BY id ASC LIMIT 5");
    $jobs = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Failed to load translation jobs: ' . $e->getMessage();
    exit;
}

if (!$jobs) {
    echo "No queued jobs.\n";
    exit;
}

foreach ($jobs as $job) {
    $jobId = (int)($job['id'] ?? 0);
    if ($jobId <= 0) {
        continue;
    }

    try {
        $result = gdy_translation_process_job($jobId);
        $status = is_array($result) && isset($result['status']) ? (string)$result['status'] : 'processed';
        echo "Job {$jobId}: {$status}\n";
    } catch (Throwable $e) {
        echo "Job {$jobId}: failed - {$e->getMessage()}\n";
    }
}
