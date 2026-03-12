<?php

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => true, 'proxied' => false, 'hint' => 'Use /api/push/subscribe'], JSON_UNESCAPED_UNICODE);
