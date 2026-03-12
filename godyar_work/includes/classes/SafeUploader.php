<?php
namespace Godyar;

final class SafeUploader
{
    
    public static function upload(array $file, array $opt): array
    {
        $destAbs = (string)($opt['dest_abs_dir'] ?? '');
        $urlPrefix = (string)($opt['url_prefix'] ?? '');
        if ($destAbs === '' || $urlPrefix === '' || $urlPrefix[0] !== '/') {
            return ['success' => false, 'error' => 'Uploader misconfigured'];
        }

        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'error' => 'No file uploaded'];
        }
        if ($err !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error: ' . $err];
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        $orig = (string)($file['name'] ?? '');
        $size = (int)($file['size'] ?? 0);

        $max = (int)($opt['max_bytes'] ?? (5 * 1024 * 1024));
        if ($size <= 0 || $size > $max) {
            return ['success' => false, 'error' => 'File size is not allowed'];
        }
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['success' => false, 'error' => 'Invalid upload source'];
        }

        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowedExt = $opt['allowed_ext'] ?? [];
        if (!is_array($allowedExt) || $ext === '' || !in_array($ext, $allowedExt, true)) {
            return ['success' => false, 'error' => 'File type is not allowed'];
        }

        
        
        $danger = ['php','phtml','php3','php4','php5','php7','phar','cgi','pl','py','js','html','htm','shtml','svg','svgz'];
        if (in_array($ext, $danger, true)) {
            return ['success' => false, 'error' => 'File type is not allowed'];
        }
        
        if ($orig !== '' && preg_match('/\.(php\d?|phtml|phar|shtml)(\.|$)/i', $orig)) {
            return ['success' => false, 'error' => 'File type is not allowed'];
        }

$mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $fi = function_exists('gdy_finfo_open') ? gdy_finfo_open(FILEINFO_MIME_TYPE) : finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $m = finfo_file($fi, $tmp);
                if (is_string($m) && $m !== '') $mime = $m;
                @finfo_close($fi);
            }
        }

        $allowedMime = $opt['allowed_mime'] ?? [];
        if (is_array($allowedMime) && isset($allowedMime[$ext]) && is_array($allowedMime[$ext])) {
            if (!in_array($mime, $allowedMime[$ext], true)) {
                return ['success' => false, 'error' => 'MIME type is not allowed'];
            }
        }

        
        
        $imgExt = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $imgExt, true)) {
            
            $info = @getimagesize($tmp);
            if (!$info || empty($info[0]) || empty($info[1])) {
                return ['success' => false, 'error' => 'Invalid image file'];
            }
            if (strpos((string)$mime, 'image/') !== 0) {
                return ['success' => false, 'error' => 'Invalid image MIME'];
            }
        }

        $destAbsNorm = str_replace(chr(92), '/', $destAbs);
        $destAbsNorm = rtrim($destAbsNorm, '/');
        if ($destAbsNorm === '') {
            return ['success' => false, 'error' => 'Invalid destination'];
        }

        if (!is_dir($destAbsNorm)) {
            if (function_exists('gdy_mkdir')) {
                gdy_mkdir($destAbsNorm, 0775, true);
            } else {
                @mkdir($destAbsNorm, 0775, true);
            }
        }

        if (!is_dir($destAbsNorm) || !is_writable($destAbsNorm)) {
            return ['success' => false, 'error' => 'Upload directory not writable'];
        }

        $prefix = (string)($opt['prefix'] ?? 'up_');
        $rand = bin2hex(random_bytes(16));
        $name = $prefix . $rand . '.' . $ext;
        $absPath = $destAbsNorm . '/' . $name;

        $moved = function_exists('gdy_move_uploaded_file')
            ? gdy_move_uploaded_file($tmp, $absPath)
            : move_uploaded_file($tmp, $absPath);

        if (!$moved) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }

        if (function_exists('gdy_chmod')) {
            gdy_chmod($absPath, 0644);
        } else {
            @chmod($absPath, 0644);
        }

        $relUrl = rtrim($urlPrefix, '/') . '/' . $name;

        return [
            'success' => true,
            'rel_url' => $relUrl,
            'abs_path' => $absPath,
            'original_name' => $orig,
            'ext' => $ext,
            'mime' => $mime,
            'size' => $size,
        ];
    }
}
