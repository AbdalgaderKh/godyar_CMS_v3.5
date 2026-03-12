<?php

$updatedAt = '';
if (isset($post) && is_array($post)) {
    if (!empty($post['updated_at'])) {
        $updatedAt = (string)$post['updated_at'];
    } elseif (!empty($post['published_at'])) {
        $updatedAt = (string)$post['published_at'];
    } elseif (!empty($post['created_at'])) {
        $updatedAt = (string)$post['created_at'];
    }
} elseif (isset($news) && is_array($news)) {
    if (!empty($news['updated_at'])) {
        $updatedAt = (string)$news['updated_at'];
    } elseif (!empty($news['published_at'])) {
        $updatedAt = (string)$news['published_at'];
    } elseif (!empty($news['created_at'])) {
        $updatedAt = (string)$news['created_at'];
    }
}

$updatedAtIso = $updatedAt !== '' ? date('c', strtotime($updatedAt)) : '';

if (!isset($canonical) || !is_string($canonical) || trim($canonical) === '') {
    if (isset($currentUrl) && is_string($currentUrl)) {
        $canonical = $currentUrl;
    } elseif (isset($_SERVER['REQUEST_URI'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $canonical = $scheme . '://' . $host . $_SERVER['REQUEST_URI'];
    } else {
        $canonical = '';
    }
}

