<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/bootstrap_v4.php';

$app = new GodyarV4\Bootstrap\App($root);
$app->run();
