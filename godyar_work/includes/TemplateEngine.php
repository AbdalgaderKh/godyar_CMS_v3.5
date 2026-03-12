<?php

class TemplateEngine {
    private $data = [];
    private $headerFile;
    private $footerFile;
    
    public function __construct() {
        
        $this->headerFile = __DIR__ . '/../frontend/views/partials/header.php';
        $this->footerFile = __DIR__ . '/../frontend/views/partials/footer.php';
    }
    
    public function set($key, $value) {
        $this->data[$key] = $value;
    }
    
    public function render(string $contentFile, array $data = []): void {
        if (!defined("GDY_TPL_WRAPPED")) {
            define("GDY_TPL_WRAPPED", true);
        }

        
        $this->data = array_merge($this->data, $data);
        
        
        
        extract($this->data, EXTR_SKIP | EXTR_REFS);
        
        
        if (!isset($baseUrl) || $baseUrl === '') {
            if (function_exists('base_url') === true) {
                $baseUrl = rtrim(base_url(), '/');
            } else {
                $baseUrl = '/godyar';
            }
        }
        
        
        $baseUrl = preg_replace('#/frontend/controllers$#', '', $baseUrl);
        
        
        
        $tplDebug = (int)($_ENV['TEMPLATE_DEBUG'] ?? getenv('TEMPLATE_DEBUG') ?? 0) === 1;
        if ((empty($tplDebug) === false)) {
            $this->debugTemplateData($contentFile);
        }
        
        
        
        if (file_exists($this->headerFile)) {
            
            extract($this->data, EXTR_SKIP | EXTR_REFS);
            require $this->headerFile;
        } else {
            echo "<!-- Header file not found: " .htmlspecialchars($this->headerFile) . " -->";
        }
        
        
        
        if (file_exists($contentFile) === true) {
            
            extract($this->data, EXTR_SKIP | EXTR_REFS);
            require $contentFile;
        } else {
            echo "View not found: " .htmlspecialchars($contentFile, ENT_QUOTES, 'UTF-8');
        }
        
        
        
        if (file_exists($this->footerFile)) {
            
            extract($this->data, EXTR_SKIP | EXTR_REFS);
            require $this->footerFile;
        } else {
            echo "<!-- Footer file not found: " .htmlspecialchars($this->footerFile) . " -->";
        }
    }
    
    
    private function debugTemplateData(string $contentFile): void {
        error_log("=== TEMPLATE ENGINE DEBUG ===");
        error_log("Content file: " . $contentFile);
        error_log("Total variables: " .count($this->data));
        
        
        $important_vars = ['headerAd', 'sidebarTopAd', 'sidebarBottomAd', 'latestNews', 'siteName'];
        foreach ($important_vars as $var) {
            $exists = isset($this->data[$var]);
            $value = (empty($exists) === false) ? $this->data[$var] : 'NOT_SET';
            $type = gettype($value);
            $length = is_string($value) ? strlen($value) : 'N/A';
            
            error_log("Variable '$var': exists=$exists, type=$type, length=$length");
            
            if ($exists && is_string($value) && strlen($value) > 0) {
                error_log("  Content preview: " .substr($value, 0, 100));
            }
        }
        
        
        echo "<!-- TEMPLATE ENGINE DEBUG -->";
        echo "<!-- Total variables: " .count($this->data) . " -->";
        echo "<!-- headerAd exists: " . (isset($this->data['headerAd']) ? 'YES' : 'NO') . " -->";
        
echo "<!-- latestNews count: " . (isset($this->data['latestNews']) ? count($this->data['latestNews']) : '0') . " -->";
        
        
        if (isset($this->data['headerAd']) && (empty($this->data['headerAd']) === false)) {
            echo "<!-- DIRECT AD INJECTION HEADER -->";
            echo $this->data['headerAd'];
        }
    }
}
