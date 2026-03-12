<?php



require_once __DIR__ . '/../../includes/frontend_compat_fixes.php';

$categoryName = gdy_safe_category_name(isset($category) ? $category : array());
