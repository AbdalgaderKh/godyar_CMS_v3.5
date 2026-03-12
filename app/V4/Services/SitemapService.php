<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Repositories\CategoryRepository;
use GodyarV4\Repositories\NewsRepository;
use GodyarV4\Repositories\PageRepository;

final class SitemapService
{
    public function __construct(
        private readonly PageRepository $pages,
        private readonly NewsRepository $news,
        private readonly CategoryRepository $categories
    ) {}

    public function indexXml(): string
    {
        $items = [
            godyar_v4_base_url('/sitemap-pages.xml'),
            godyar_v4_base_url('/sitemap-news.xml'),
            godyar_v4_base_url('/sitemap-categories.xml'),
        ];
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        foreach ($items as $url) {
            $xml[] = '<sitemap><loc>' . e($url) . '</loc></sitemap>';
        }
        $xml[] = '</sitemapindex>';
        return implode("\n", $xml);
    }

    public function pagesXml(): string
    {
        $rows = [];
        foreach (['ar', 'en', 'fr'] as $locale) {
            $rows[] = ['loc' => godyar_v4_url($locale), 'lastmod' => date('c')];
            $rows[] = ['loc' => godyar_v4_url($locale, 'contact'), 'lastmod' => date('c')];
            foreach ($this->pages->all($locale) as $page) {
                $rows[] = [
                    'loc' => godyar_v4_url($locale, 'page/' . ($page['slug'] ?? '')),
                    'lastmod' => (string) ($page['updated_at'] ?? date('c')),
                ];
            }
        }
        return $this->renderUrlSet($rows);
    }

    public function newsXml(): string
    {
        $rows = [];
        foreach (['ar'] as $locale) {
            foreach ($this->news->all($locale, 500) as $news) {
                $rows[] = [
                    'loc' => godyar_v4_url($locale, 'news/' . ($news['slug'] ?? '')),
                    'lastmod' => (string) ($news['updated_at'] ?? $news['published_at'] ?? date('c')),
                ];
            }
        }
        return $this->renderUrlSet($rows);
    }

    public function categoriesXml(): string
    {
        $rows = [];
        foreach (['ar'] as $locale) {
            foreach ($this->categories->all($locale, 200) as $category) {
                $rows[] = [
                    'loc' => godyar_v4_url($locale, 'category/' . ($category['slug'] ?? '')),
                    'lastmod' => (string) ($category['updated_at'] ?? date('c')),
                ];
            }
        }
        return $this->renderUrlSet($rows);
    }

    private function renderUrlSet(array $rows): string
    {
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        foreach ($rows as $row) {
            $xml[] = '<url><loc>' . e($row['loc']) . '</loc><lastmod>' . e($row['lastmod']) . '</lastmod></url>';
        }
        $xml[] = '</urlset>';
        return implode("\n", $xml);
    }
}
