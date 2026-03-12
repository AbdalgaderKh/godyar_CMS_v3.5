<?php
declare(strict_types=1);

if (!defined('GDY_V4_BOOTSTRAPPED')) {
    define('GDY_V4_BOOTSTRAPPED', true);
}

$projectRoot = __DIR__;

require_once $projectRoot . '/includes/bootstrap.php';
require_once $projectRoot . '/app/V4/Support/helpers.php';

godyar_v4_bootstrap_autoload($projectRoot);
