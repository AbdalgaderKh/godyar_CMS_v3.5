<?php

if (!headers_sent()) {
    header('Location: db_audit.php');
}
require_once __DIR__ . '/db_audit.php';
exit;
