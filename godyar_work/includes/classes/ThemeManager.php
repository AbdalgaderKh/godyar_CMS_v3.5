<?php

if (!class_exists('ThemeManager')) {
    final class ThemeManager
    {
        private ?PDO $pdo;

        public function __construct(?PDO $pdo = null)
        {
            $this->pdo = $pdo;
        }

        public static function instance(?PDO $pdo = null): self
        {
            return new self($pdo);
        }

        public function getCurrentTheme(): string
        {
            
            if (class_exists('Godyar\\Theme\\ThemeManager')) {
                try {
                    return (string)\Godyar\Theme\ThemeManager::getActiveThemeId();
                } catch (\Throwable $e) {
                    
                }
            }

            
            try {
                $pdo = $this->pdo;
                if (!$pdo && class_exists('Godyar\\DB') && method_exists('Godyar\\DB', 'pdoOrNull')) {
                    $pdo = \Godyar\DB::pdoOrNull();
                }
                if ($pdo instanceof PDO) {
                    $col = function_exists('gdy_settings_value_column') ? gdy_settings_value_column($pdo) : 'setting_value';
                    $stmt = $pdo->prepare("SELECT {$col} FROM settings WHERE setting_key IN ('theme.front','theme_front','frontend_theme') LIMIT 1");
                    $stmt->execute();
                    $v = (string)($stmt->fetchColumn() ?: 'default');
                    return $v !== '' ? $v : 'default';
                }
            } catch (\Throwable $e) {
                
            }

            return 'default';
        }

        
        public function getThemes(): array
        {
            
            return ['default', 'red', 'blue', 'green', 'dark'];
        }
    }
}
