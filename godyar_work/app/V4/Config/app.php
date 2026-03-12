<?php
return [
    'name' => 'Godyar CMS v4',
    'base_url' => function_exists('base_url') ? base_url() : '',
    'timezone' => 'Asia/Riyadh',
    'default_locale' => 'ar',
    'supported_locales' => ['ar', 'en', 'fr'],
    'debug' => false,
];
