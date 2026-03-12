<?php
namespace App\Http\Controllers;

final class LegacyIncludeController
{
    
    private string $baseDir;

    public function __construct(string $baseDir)
    {
        $real = realpath($baseDir);
        $this->baseDir = $real ? rtrim($real, DIRECTORY_SEPARATOR) : rtrim($baseDir, '/');
    }

    
    public function include(string $relativeFile, array $get = []): void
    {
        
        $relativeFile = str_replace(["\0", "\r", "\n"], '', $relativeFile);
        
        $relativeFile = ltrim($relativeFile, "/\\");

        
        if ($relativeFile === '' || str_contains($relativeFile, '..') || str_contains($relativeFile, ':')) {
            http_response_code(400);
            echo 'Bad request.';
            exit;
        }

        foreach ($get as $k => $v) {
            $_GET[$k] = ($v === null) ? '' : (is_bool($v) ? ($v ? '1' : '0') : (string)$v);
        }

        $candidate = $this->baseDir . DIRECTORY_SEPARATOR . $relativeFile;

        $real = realpath($candidate);
        if ($real === false || is_file($real) === false) {
            http_response_code(500);
            echo 'Controller not found.';
            exit;
        }

        
        $base = $this->baseDir .DIRECTORY_SEPARATOR;
        $realNorm = rtrim(str_replace(['\\'], ['/'], $real), '/');
        $baseNorm = rtrim(str_replace(['\\'], ['/'], $base), '/');

        if (strpos($realNorm, $baseNorm) !== 0) {
            http_response_code(403);
            echo 'Forbidden.';
            exit;
        }

        require $real;
        exit;
    }
}
