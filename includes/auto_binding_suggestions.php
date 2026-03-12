<?php
function gdy_auto_binding_suggestions() {
    $file = __DIR__ . '/../reports/binding_suggestions.json';
    if (!is_file($file)) return array();
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : array();
}
