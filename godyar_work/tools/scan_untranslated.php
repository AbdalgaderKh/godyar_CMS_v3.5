<?php

$dirs = [
    __DIR__ . '/../frontend/views',
    __DIR__ . '/../frontend/partials',
    __DIR__ . '/../templates'
];

function scanFile($file)
{
    $lines = file($file);
    $results = [];

    foreach ($lines as $i => $line) {

        
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $line)) {

            
            if (strpos($line, "__(") === false) {

                $results[] = [
                    "line" => $i + 1,
                    "text" => trim($line)
                ];
            }
        }
    }

    return $results;
}

foreach ($dirs as $dir) {

    if (!is_dir($dir)) continue;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    foreach ($iterator as $file) {

        if ($file->isDir()) continue;

        $ext = pathinfo($file, PATHINFO_EXTENSION);

        if (!in_array($ext, ['php','html'])) continue;

        $found = scanFile($file);

        if ($found) {

            echo "\nFILE: " . $file->getPathname() . "\n";

            foreach ($found as $r) {
                echo "Line ".$r['line']." : ".$r['text']."\n";
            }

        }

    }

}