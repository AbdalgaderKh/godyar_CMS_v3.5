<?php

$isLoggedIn = $isLoggedIn ?? 0;
$currentUser = $currentUser ?? [];
$isAdmin = $isAdmin ?? 0;
?>
<!doctype html>
<html lang="ar" dir="rtl" data-theme="light">
<head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
  <?php require __DIR__ . '/theme_head.php'; ?>
</head>

<body class="rtl"
      data-auth="<?= (int)$isLoggedIn ?>"
      data-user-id="<?= (int)($currentUser['id'] ?? 0) ?>"
      data-admin="<?= (int)$isAdmin ?>">