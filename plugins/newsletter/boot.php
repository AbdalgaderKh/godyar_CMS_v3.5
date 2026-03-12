<?php
$hooks->on('theme.footer', static function(array $payload): string {
    return '<script>window.GODYAR_NEWSLETTER_ENABLED=true;</script>';
});
