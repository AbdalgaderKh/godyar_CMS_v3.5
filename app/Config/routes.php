<?php

use GodyarV4\Controllers\CategoryController;
use GodyarV4\Controllers\ContactController;
use GodyarV4\Controllers\HomeController;
use GodyarV4\Controllers\NewsController;
use GodyarV4\Controllers\PageController;
use GodyarV4\Controllers\SitemapController;

return [
    ['GET', '#^/$#', [HomeController::class, 'index']],
    ['GET', '#^/(ar|en|fr)/?$#', [HomeController::class, 'index']],
    ['GET', '#^/(ar|en|fr)/page/([^/]+)/?$#', [PageController::class, 'show']],
    ['GET', '#^/(ar|en|fr)/news/([^/]+)/?$#', [NewsController::class, 'show']],
    ['GET', '#^/(ar|en|fr)/category/([^/]+)/?$#', [CategoryController::class, 'show']],
    ['GET', '#^/(ar|en|fr)/contact/?$#', [ContactController::class, 'index']],
    ['POST', '#^/(ar|en|fr)/contact/send/?$#', [ContactController::class, 'send']],
    ['GET', '#^/sitemap\.xml$#', [SitemapController::class, 'index']],
];
