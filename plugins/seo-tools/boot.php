<?php
$hooks->on('theme.head', static function(array $payload): string {
    return '<meta name="x-godyar-plugin" content="seo-tools">';
});
