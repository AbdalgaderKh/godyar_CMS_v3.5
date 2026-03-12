<?php
require_once __DIR__ . '/../_admin_guard.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';

use Godyar\Auth;

header('Content-Type: application/json; charset=utf-8');

$pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
if (!Auth::isLoggedIn()) {
  http_response_code(401);
  echo json_encode(['ok' => false,'error' => 'unauthorized'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
  exit;
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q === '' || mb_strlen($q, 'UTF-8') < 2) {
  echo json_encode(['ok' => true,'items' => []], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
  exit;
}

$like = '%' . $q . '%';

if (!($pdo instanceof \PDO)) {
  http_response_code(500);
  echo json_encode(['ok' => false,'items' => []]);
  exit;
}

try {
  
  $sql = "SELECT n.id, n.title, n.slug, n.published_at, c.name AS category_name,
                 ((CASE WHEN n.title LIKE :q_exact THEN 5 ELSE 0 END) +
                  (CASE WHEN n.slug LIKE :q THEN 2 ELSE 0 END) +
                  (CASE WHEN n.title LIKE :q THEN 3 ELSE 0 END)) AS score
          FROM news n
          LEFT JOIN categories c ON c.id = n.category_id
          WHERE (n.title LIKE :q OR n.slug LIKE :q)
          ORDER BY score DESC, n.published_at DESC, n.id DESC
          LIMIT 8";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':q' => $like, ':q_exact' => $q . '%']);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $items = [];
  foreach ($rows as $r) {
    $id = (int)($r['id'] ?? 0);
    if ($id <= 0) continue;
    $slug = (string)($r['slug'] ?? '');
    
    $url = '/news/id/' . $id . ($slug !== '' ? ('/' .rawurlencode($slug)) : '');
    $items[] = [
      'id' => $id,
      'title' => (string)($r['title'] ?? ''),
      'url' => $url,
      'meta' => trim((string)($r['category_name'] ?? '')) . (((string)($r['published_at'] ?? '') !== '') ? ' • ' . (string)$r['published_at'] : ''),
    ];
  }

  echo json_encode(['ok' => true,'items' => $items], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
} catch (\Throwable $e) {
  error_log('[InternalLinks] ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false,'error' => 'server_error'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
