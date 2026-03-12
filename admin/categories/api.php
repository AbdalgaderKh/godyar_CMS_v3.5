<?php
require_once __DIR__ . '/../_admin_guard.php';

$rootPath = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';

$bootstrapPath = $rootPath . '/includes/bootstrap.php';
if (is_file($bootstrapPath) === false) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'error' => __('t_b3cb8f49d9', 'bootstrap.php غير موجود. المسار: ') . $bootstrapPath,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}
require_once $bootstrapPath;

if (session_status() !== PHP_SESSION_ACTIVE) {
    gdy_session_start();
}

if (function_exists('h') === false) {
    function h($v): string {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}

$user = $_SESSION['user'] ?? null;
$isAdmin = is_array($user) && in_array(strtolower(trim((string)($user['role'] ?? ''))), ['admin','administrator','super_admin','superadmin','manager'], true);

header('Content-Type: application/json; charset=utf-8');

if (($isAdmin === false)) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => __('t_03a633f671', 'غير مصرح لك بالوصول إلى هذا المورد (مدير النظام فقط).'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

$pdo = gdy_pdo_safe();
if (($pdo instanceof PDO) === false) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => __('t_121fb45a70', 'الاتصال بقاعدة البيانات غير مهيأ (PDO غير متوفر).'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    if ($action === 'list') {
        
        $sql = "
            SELECT 
                c . *,
                (
                    SELECT COUNT(*) 
                    FROM news n 
                    WHERE n .category_id = c .id
                ) AS news_count
            FROM categories c
            ORDER BY c .id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $total = count($categories);
        $withNews = 0;
        $withoutNews = 0;

        foreach ($categories as $cat) {
            $cnt = (int)($cat['news_count'] ?? 0);
            if ($cnt > 0) {
                $withNews++;
            } else {
                $withoutNews++;
            }
        }

        $response = [
            'ok' => true,
            'stats' => [
                'total' => $total,
                'with_news' => $withNews,
                'without_news' => $withoutNews,
            ],
            'categories' => $categories,
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    }

    
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => __('t_91c373ec9b', 'action غير مدعوم في هذا الـ API.'),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;

} catch (\Throwable $e) {
    error_log('[categories_api] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => __('t_b29ffb3972', 'حدث خطأ أثناء جلب الأقسام. راجع سجلات الأخطاء.'),
        'trace' => false, 
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}
