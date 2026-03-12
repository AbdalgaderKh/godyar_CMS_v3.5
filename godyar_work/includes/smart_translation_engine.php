<?php

if (!function_exists('gdy_env_value')) {
    function gdy_env_value($key, $default = '')
    {
        $v = getenv($key);
        return ($v !== false && $v !== null) ? $v : $default;
    }
}

if (!function_exists('gdy_translation_db')) {
    function gdy_translation_db()
    {
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            return $GLOBALS['pdo'];
        }
        if (function_exists('gdy_pdo_safe')) {
            return gdy_pdo_safe();
        }
        throw new Exception('PDO connection not available.');
    }
}

if (!function_exists('gdy_translation_fetch_news')) {
    function gdy_translation_fetch_news($newsId)
    {
        $pdo = gdy_translation_db();
        $stmt = $pdo->prepare("SELECT id, title, excerpt, content FROM news WHERE id = ? LIMIT 1");
        $stmt->execute(array((int)$newsId));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('gdy_translation_enqueue_news')) {
    function gdy_translation_enqueue_news($newsId, $targetLang, $createdBy = null)
    {
        $news = gdy_translation_fetch_news($newsId);
        if (!$news) {
            throw new Exception('News article not found.');
        }

        $payload = array(
            'title' => (string)$news['title'],
            'excerpt' => isset($news['excerpt']) ? (string)$news['excerpt'] : '',
            'content' => isset($news['content']) ? (string)$news['content'] : '',
        );

        $pdo = gdy_translation_db();
        $stmt = $pdo->prepare("
            INSERT INTO translation_jobs
            (entity_type, entity_id, source_lang, target_lang, fields_json, status, provider, created_by, created_at)
            VALUES
            ('news', ?, 'ar', ?, ?, 'queued', 'manual', ?, NOW())
        ");
        $stmt->execute(array((int)$newsId, (string)$targetLang, json_encode($payload), $createdBy));

        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('gdy_translation_fake_provider')) {
    function gdy_translation_fake_provider($text, $targetLang)
    {
        
        $prefix = '[' . strtoupper($targetLang) . ' draft] ';
        return $prefix . trim((string)$text);
    }
}

if (!function_exists('gdy_translation_process_job')) {
    function gdy_translation_process_job($jobId)
    {
        $pdo = gdy_translation_db();

        $stmt = $pdo->prepare("SELECT * FROM translation_jobs WHERE id = ? LIMIT 1");
        $stmt->execute(array((int)$jobId));
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$job) {
            throw new Exception('Translation job not found.');
        }

        if ($job['status'] === 'finished') {
            return array('ok' => true, 'message' => 'Already finished.');
        }

        $fields = json_decode($job['fields_json'], true);
        if (!is_array($fields)) {
            $fields = array();
        }

        $pdo->prepare("UPDATE translation_jobs SET status='processing', started_at=NOW() WHERE id=?")->execute(array((int)$jobId));

        $insert = $pdo->prepare("
            INSERT INTO translation_suggestions
            (job_id, field_key, original_text, translated_text, confidence, is_approved, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");

        foreach ($fields as $fieldKey => $originalText) {
            $translated = gdy_translation_fake_provider($originalText, $job['target_lang']);
            $insert->execute(array(
                (int)$jobId,
                (string)$fieldKey,
                (string)$originalText,
                (string)$translated,
                0.35
            ));
        }

        $pdo->prepare("UPDATE translation_jobs SET status='finished', finished_at=NOW() WHERE id=?")->execute(array((int)$jobId));

        return array('ok' => true, 'message' => 'Suggestions generated.');
    }
}

if (!function_exists('gdy_translation_apply_suggestion_set')) {
    function gdy_translation_apply_suggestion_set($jobId)
    {
        $pdo = gdy_translation_db();

        $jobStmt = $pdo->prepare("SELECT * FROM translation_jobs WHERE id = ? LIMIT 1");
        $jobStmt->execute(array((int)$jobId));
        $job = $jobStmt->fetch(PDO::FETCH_ASSOC);
        if (!$job) {
            throw new Exception('Job not found.');
        }

        $sStmt = $pdo->prepare("SELECT field_key, translated_text FROM translation_suggestions WHERE job_id = ? AND is_approved = 1");
        $sStmt->execute(array((int)$jobId));
        $rows = $sStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $up = $pdo->prepare("
                INSERT INTO i18n_fields (entity_type, entity_id, lang_code, field_name, translated_value, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE translated_value = VALUES(translated_value), updated_at = NOW()
            ");
            $up->execute(array(
                $job['entity_type'],
                (int)$job['entity_id'],
                $job['target_lang'],
                $row['field_key'],
                $row['translated_text']
            ));
        }

        return count($rows);
    }
}
