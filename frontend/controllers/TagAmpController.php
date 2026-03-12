<?php
namespace App\Http\Controllers;

final class TagAmpController
{
    public function __invoke(): void
    {
        $slug = $_GET['slug'] ?? '';
        $slug = is_string($slug) ? $slug : '';
        $to = '/tag/' . rawurlencode($slug);
        header('Location: ' . $to, true, 302);
        exit;
    }
}
