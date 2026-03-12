<?php

return [
    'name' => 'Godyar CMS',
    'version' => '4.0.0-skeleton',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL),
    'base_url' => getenv('APP_URL') ?: '',
    'default_locale' => getenv('APP_LOCALE') ?: 'ar',
    'supported_locales' => ['ar', 'en', 'fr'],
    'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Riyadh',
];
