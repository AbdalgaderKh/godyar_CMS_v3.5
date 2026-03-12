<?php

if (!function_exists('gdy_slider_table')) {
    function gdy_slider_table(\PDO $pdo): string
    {
        
        
        if (function_exists('gdy_db_table_exists') && gdy_db_table_exists($pdo, 'slider')) {
            return 'slider';
        }

        
        if (function_exists('gdy_db_table_exists') && gdy_db_table_exists($pdo, 'sliders')) {
            return 'sliders';
        }

        
        try {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS `slider` (\n".
                "  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,\n".
                "  `title` VARCHAR(255) NOT NULL,\n".
                "  `subtitle` VARCHAR(255) NULL DEFAULT NULL,\n".
                "  `image_path` VARCHAR(1024) NULL DEFAULT NULL,\n".
                "  `link_url` VARCHAR(1024) NULL DEFAULT NULL,\n".
                "  `is_active` TINYINT(1) NOT NULL DEFAULT 1,\n".
                "  `sort_order` INT NOT NULL DEFAULT 0,\n".
                "  `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,\n".
                "  `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n".
                "  PRIMARY KEY (`id`),\n".
                "  KEY `idx_slider_active_sort` (`is_active`, `sort_order`, `id`)\n".
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (\Throwable $e) {
            
            error_log('[Godyar Slider] failed to create slider table: ' . $e->getMessage());
        }

        return 'slider';
    }
}

if (!function_exists('gdy_slider_qt')) {
    
    function gdy_slider_qt(string $table): string
    {
        if (function_exists('gdy_db_quote_ident')) {
            return gdy_db_quote_ident($table);
        }
        
        if (!preg_match('~^[a-zA-Z0-9_]+$~', $table)) {
            throw new \RuntimeException('Invalid identifier');
        }
        return '`' . $table . '`';
    }
}
