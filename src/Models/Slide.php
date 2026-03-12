<?php

namespace App\Models;

class Slide
{
    
    public static function all(): array
    {
        try {
            $m = new \Godyar\Models\Slide();
            return $m->all();
        } catch (\Throwable $e) {
            error_log('[App\\Models\\Slide::all] ' . $e->getMessage());
            return [];
        }
    }
}
