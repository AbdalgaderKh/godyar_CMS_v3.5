<?php
require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/_news_helpers.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';

use Godyar\Auth;

header('Content-Type: application/json; charset=utf-8');

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}
$pdo = gdy_pdo_safe();
if (!($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_unavailable'], JSON_UNESCAPED_UNICODE);
    exit;
}
$newsId = (int)($_GET['news_id'] ?? 0);
$leftId = (int)($_GET['left'] ?? 0);
$rightId = (int)($_GET['right'] ?? 0);
if ($newsId <= 0 || $leftId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'missing_params'], JSON_UNESCAPED_UNICODE);
    exit;
}
$left = gdy_get_revision_payload($pdo, $leftId);
if (!$left) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'left_not_found'], JSON_UNESCAPED_UNICODE);
    exit;
}
$right = null;
if ($rightId > 0) { $right = gdy_get_revision_payload($pdo, $rightId); }
if (!$right) {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $newsId, PDO::PARAM_INT);
    $stmt->execute();
    $right = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
$fields = ['title'=>'العنوان','excerpt'=>'المقدمة','content'=>'المحتوى','seo_title'=>'عنوان SEO','seo_description'=>'وصف SEO','status'=>'الحالة','published_at'=>'تاريخ النشر'];
$normalize = function ($v) {
    $s = trim((string)$v);
    $s = preg_replace('/\s+/u', ' ', $s);
    return $s;
};
$preview = function (string $s): string {
    return mb_substr($s, 0, 500);
};
$rows = [];
$changed = 0;
foreach ($fields as $key => $label) {
    $lv = $normalize($left[$key] ?? '');
    $rv = $normalize($right[$key] ?? '');
    $isChanged = $lv !== $rv;
    if ($isChanged) { $changed++; }
    $rows[] = [
        'field' => $key,
        'label' => $label,
        'changed' => $isChanged,
        'left' => $preview($lv),
        'right' => $preview($rv),
        'left_length' => mb_strlen($lv),
        'right_length' => mb_strlen($rv),
    ];
}
$meta = [
    'left' => [
        'id' => $leftId,
        'label' => (string)($left['created_at'] ?? ''),
        'action' => (string)($left['action'] ?? 'revision'),
    ],
    'right' => [
        'id' => $rightId,
        'label' => $rightId > 0 ? (string)($right['created_at'] ?? '') : 'النسخة الحالية',
        'action' => $rightId > 0 ? (string)($right['action'] ?? 'revision') : 'current',
    ],
    'changed_count' => $changed,
];

echo json_encode(['ok' => true, 'rows' => $rows, 'meta' => $meta], JSON_UNESCAPED_UNICODE);
