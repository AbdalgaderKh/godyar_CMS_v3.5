<?php
use GodyarV4\Controllers\AdminAdvancedController;
use GodyarV4\Controllers\AdminGovernanceController;
use GodyarV4\Controllers\AdminProController;
use GodyarV4\Controllers\AdminExperienceController;
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
    ['GET', '#^/v4/admin/redirects/?$#', [AdminGovernanceController::class, 'redirects']],
    ['POST', '#^/v4/admin/redirects/save/?$#', [AdminGovernanceController::class, 'saveRedirect']],
    ['POST', '#^/v4/admin/redirects/delete/?$#', [AdminGovernanceController::class, 'deleteRedirect']],
    ['GET', '#^/v4/admin/seo-audit/?$#', [AdminGovernanceController::class, 'seoAudit']],
    ['GET', '#^/v4/admin/media-trash/?$#', [AdminGovernanceController::class, 'mediaTrash']],
    ['POST', '#^/v4/admin/media-trash/restore/?$#', [AdminGovernanceController::class, 'restoreTrash']],
    ['GET', '#^/v4/admin/system-health/?$#', [AdminGovernanceController::class, 'systemHealth']],
    ['GET', '#^/v4/admin/plugins/?$#', [AdminProController::class, 'plugins']],
    ['POST', '#^/v4/admin/plugins/toggle/?$#', [AdminProController::class, 'togglePlugin']],
    ['GET', '#^/v4/admin/themes/?$#', [AdminExperienceController::class, 'themes']],
    ['POST', '#^/v4/admin/themes/activate/?$#', [AdminExperienceController::class, 'activateTheme']],
    ['GET', '#^/v4/admin/hooks/?$#', [AdminExperienceController::class, 'hooks']],
    ['GET', '#^/v4/admin/theme-settings/?$#', [AdminExperienceController::class, 'themeSettings']],
    ['POST', '#^/v4/admin/theme-settings/save/?$#', [AdminExperienceController::class, 'saveThemeSettings']],
    ['GET', '#^/v4/admin/theme-customizer/?$#', [AdminExperienceController::class, 'themeCustomizer']],
    ['POST', '#^/v4/admin/theme-customizer/save/?$#', [AdminExperienceController::class, 'saveThemeCustomizer']],
    ['GET', '#^/v4/admin/media-optimizer/?$#', [AdminProController::class, 'mediaOptimizer']],
    ['POST', '#^/v4/admin/media-optimizer/run/?$#', [AdminProController::class, 'optimizeMedia']],
    ['GET', '#^/v4/admin/cache-center/?$#', [AdminProController::class, 'cacheCenter']],
    ['POST', '#^/v4/admin/cache-center/clear/?$#', [AdminProController::class, 'clearCache']],
    ['GET', '#^/v4/admin/revisions/?$#', [AdminAdvancedController::class, 'revisions']],
    ['POST', '#^/v4/admin/revisions/restore/?$#', [AdminAdvancedController::class, 'restoreDraft']],
    ['POST', '#^/v4/admin/revisions/live-restore/?$#', [AdminAdvancedController::class, 'restoreLive']],
    ['GET', '#^/v4/admin/seo-preview/?$#', [AdminAdvancedController::class, 'seoPreview']],
    ['POST', '#^/v4/admin/seo-preview/save/?$#', [AdminAdvancedController::class, 'saveSeoOverride']],
    ['GET', '#^/v4/admin/media-cleanup/?$#', [AdminAdvancedController::class, 'mediaCleanup']],
    ['POST', '#^/v4/admin/media-cleanup/queue/?$#', [AdminAdvancedController::class, 'queueMedia']],
    ['POST', '#^/v4/admin/media-cleanup/unqueue/?$#', [AdminAdvancedController::class, 'unqueueMedia']],
    ['POST', '#^/v4/admin/media-cleanup/archive/?$#', [AdminAdvancedController::class, 'archiveMedia']],
    ['GET', '#^/sitemap\.xml$#', [SitemapController::class, 'index']],
    ['GET', '#^/sitemap-pages\.xml$#', [SitemapController::class, 'pages']],
    ['GET', '#^/sitemap-news\.xml$#', [SitemapController::class, 'news']],
    ['GET', '#^/sitemap-categories\.xml$#', [SitemapController::class, 'categories']],
];
