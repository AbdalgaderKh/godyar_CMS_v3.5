<?php

class Upload {
    
    private array $allowedMimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        
    ];

    private int $maxBytes;
    private string $destRelDir;

    public function __construct(string $destRelDir = '/uploads/', int $maxMB = 5) {
        $this->destRelDir = '/' .trim($destRelDir, '/') . '/';
        $this->maxBytes = max(1, $maxMB) * 1024 * 1024;
    }

    
    public function uploadImage(array $file): ?array {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
        $tmp = (string)($file['tmp_name'] ?? '');
        $size = (int)($file['size'] ?? 0);
        if ($tmp === '' || $size <= 0 || $size > $this->maxBytes) return null;

        if (!function_exists('finfo_open')) return null;
        $finfo = gdy_finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) return null;
        $mime = (string)gdy_finfo_file($finfo, $tmp);
        gdy_finfo_close($finfo);

        if (!isset($this->allowedMimeToExt[$mime])) return null;
        $ext = $this->allowedMimeToExt[$mime];

                
        $info = @getimagesize($tmp);
        if (!$info || empty($info[0]) || empty($info[1])) return null;
$root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 1);
  
  $destAbs = rtrim($root, '/\\') . $this->destRelDir;
        if (!is_dir($destAbs)) gdy_mkdir($destAbs, 0775, true);

        $name = bin2hex(random_bytes(16)) . '.' . $ext;
  $abs = rtrim($destAbs, '/\\') .DIRECTORY_SEPARATOR . $name;

        if (!gdy_move_uploaded_file($tmp, $abs)) return null;

        $rel = rtrim($this->destRelDir, '/') . '/' . $name;
        return ['file_name' => $name,'file_rel' => $rel,'mime' => $mime,'size' => $size];
    }
}
