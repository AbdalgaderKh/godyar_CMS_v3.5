<?php

if (!defined('GODYAR_CMS_VERSION')) {
    define('GODYAR_CMS_VERSION', 'v3.5.0-stable');
}
if (!defined('GODYAR_CMS_COPYRIGHT')) {
    define('GODYAR_CMS_COPYRIGHT', 'Godyar News Platform');
}
if (!function_exists('gdy_cms_badge')) {
    function gdy_cms_badge(): string
    {
        return trim((string)GODYAR_CMS_COPYRIGHT) . ' ' . trim((string)GODYAR_CMS_VERSION);
    }
}
