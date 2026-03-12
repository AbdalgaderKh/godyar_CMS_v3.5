<?php

$PUBLIC_ROOT = realpath(__DIR__ . '/..');
if ($PUBLIC_ROOT === false) $PUBLIC_ROOT = dirname(__DIR__);

$CFG_FILE = $PUBLIC_ROOT . '/storage/config/site_theme.php';

$theme = null;
if (is_file($CFG_FILE)) {
  $v = include $CFG_FILE;
  if (is_string($v) && $v !== '') $theme = $v;
}

echo '<link rel="stylesheet" href="/assets/css/themes/theme-core.css">', "\n";

$themePath = $theme ?: 'assets/css/themes/theme-default.css';
$themePath = ltrim(str_replace(['\\', '//'], ['/', '/'], $themePath), '/');

$file = $PUBLIC_ROOT . '/' . $themePath;
$ver = (is_file($file) ? (string)@filemtime($file) : (string)time());

echo '<link rel="stylesheet" href="/' . htmlspecialchars($themePath, ENT_QUOTES, 'UTF-8') . '?v=' . htmlspecialchars($ver, ENT_QUOTES, 'UTF-8') . '">', "\n";
