<?php
namespace App\Http\Controllers;

final class CategoryAmpController
{
    public function __invoke(): void
    {
        $slug = $_GET['slug'] ?? '';
        $slug = is_string($slug) ? $slug : '';
        $to = '/category/' . rawurlencode($slug);
        header('Location: ' . $to, true, 302);
        exit;
    }
}
