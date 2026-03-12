<?php
require_once __DIR__ . '/../_admin_guard.php';

require_once __DIR__ . '/../../includes/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    gdy_session_start();
}

$user = $_SESSION['user'] ?? null;
$isAdmin = is_array($user) && in_array(strtolower(trim((string)($user['role'] ?? ''))), ['admin','administrator','super_admin','superadmin','manager'], true);

if (!$isAdmin) {
    header('Location: ../login.php');
    exit;
}

header('Location: ../index.php');
exit;
