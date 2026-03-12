<?php

$base = defined('GODYAR_BASE_URL') ? rtrim((string)GODYAR_BASE_URL, '/') : '';
$adminBase = $base . '/admin';

$currentPage = $currentPage ?? 'dashboard';
$pageTitle = $pageTitle ?? (function_exists('__') ? __('dashboard', [], 'لوحة التحكم') : 'لوحة التحكم');
$pageSubtitle = $pageSubtitle ?? '';
$breadcrumbs = $breadcrumbs ?? [];
$pageActionsHtml = $pageActionsHtml ?? '';
$pageHead = $pageHead ?? '';

require_once __DIR__ . '/header.php';

?>
<div class = "gdy-admin-app">
  <?php require_once __DIR__ . '/sidebar.php'; ?>
  <div class = "gdy-admin-main">
  <?php require_once __DIR__ . '/topbar.php'; ?>
    <main class = "admin-content">
      <div class = "gdy-admin-container">

        <div class = "gdy-page-header">
          <?php if (!empty($breadcrumbs) && is_array($breadcrumbs)): ?>
            <div class = "gdy-breadcrumb mb-2">
              <?php
                $i = 0;
                foreach ($breadcrumbs as $label => $href) {
                  if ($i > 0) echo ' / ';
                  $i++;
                  $label = (string)$label;
                  $href = $href ? (string)$href : '';
                  if ($href !== '') {
                    echo '<a href="' .h($href) . '" class="text-decoration-none">' .h($label) . '</a>';
                  } else {
                    echo '<span>' .h($label) . '</span>';
                  }
                }
              ?>
            </div>
          <?php endif; ?>

          <div class = "d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
              <h1 class = "gdy-page-title h4 mb-0"><?php echo h((string)$pageTitle); ?></h1>
              <?php if ((string)$pageSubtitle !== ''): ?>
                <div class = "gdy-page-subtitle text-muted"><?php echo h((string)$pageSubtitle); ?></div>
              <?php endif; ?>
            </div>

            <?php if ((string)$pageActionsHtml !== ''): ?>
              <div class = "d-flex flex-wrap gap-2 align-items-center">
                <?php echo (string)$pageActionsHtml; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

