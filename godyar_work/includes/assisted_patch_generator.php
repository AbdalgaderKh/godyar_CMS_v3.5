<?php
function gdy_assisted_patch_rows() {
    $file = __DIR__ . '/../reports/assisted_patch_rows.json';
    if (!is_file($file)) return array();
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : array();
}

function gdy_generate_unified_patch() {
    $rows = gdy_assisted_patch_rows();
    $out = array();
    foreach ($rows as $row) {
        $out[] = 'FILE: ' . $row['file'];
        $out[] = '- ' . $row['before'];
        $out[] = '+ ' . $row['after'];
        $out[] = '';
    }
    return implode("\n", $out);
}
