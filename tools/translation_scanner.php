
<?php

$root = __DIR__ . "/../../";
$viewDirs = [
    $root . "frontend/views",
    $root . "frontend/partials",
    $root . "templates"
];

$extensions = ['php','html','twig'];
$results = [];

function scanFile($file){
    $lines = file($file);
    $out = [];
    foreach($lines as $i=>$line){
        
        if(preg_match('/[\x{0600}-\x{06FF}]/u', $line)){
            if(strpos($line,"__(") === false){
                $out[] = [
                    "line"=>$i+1,
                    "text"=>trim($line)
                ];
            }
        }
    }
    return $out;
}

foreach($viewDirs as $dir){
    if(!is_dir($dir)) continue;
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach($rii as $file){
        if($file->isDir()) continue;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if(!in_array($ext,$extensions)) continue;

        $found = scanFile($file);
        if($found){
            $results[$file->getPathname()] = $found;
        }
    }
}

header("Content-Type: application/json");
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
