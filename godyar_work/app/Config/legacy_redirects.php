<?php

return [
    [
        'method' => 'GET',
        'pattern' => '#^/home\.php$#',
        'target' => '/',
        'status' => 301,
    ],
    [
        'method' => 'GET',
        'pattern' => '#^/index\.php$#',
        'target' => '/',
        'status' => 301,
    ],
    [
        'method' => 'GET',
        'pattern' => '#^/contact(?:/index\.php)?$#',
        'target' => '/contact',
        'status' => 301,
    ],
    [
        'method' => 'GET',
        'pattern' => '#^/page/contact(?:/index\.php)?$#',
        'target' => '/contact',
        'status' => 301,
    ],
    [
        'method' => 'GET',
        'pattern' => '#^/contact-submit\.php$#',
        'target' => '/contact',
        'status' => 301,
    ],
    [
        'method' => 'GET',
        'pattern' => '#^/category\.php$#',
        'target' => '/category/{slug}',
        'query_map' => ['slug' => '{slug}'],
        'status' => 301,
    ],
    [
        'method' => 'GET',
        'pattern' => '#^/news_single\.php$#',
        'target' => '/news/{slug}',
        'query_map' => ['slug' => '{slug}'],
        'status' => 301,
    ],
    [
        'method' => 'GET',
        'pattern' => '#^/news_detail\.php$#',
        'target' => '/news/{slug}',
        'query_map' => ['slug' => '{slug}'],
        'status' => 301,
    ],
    [
        'method' => 'GET',
        'pattern' => '#^/news_report\.php$#',
        'target' => '/news/{slug}',
        'query_map' => ['slug' => '{slug}'],
        'status' => 301,
    ],
];
