<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Bootstrap\App;
use GodyarV4\Repositories\CategoryRepository;
use GodyarV4\Repositories\NewsRepository;
use GodyarV4\Repositories\PageRepository;
use GodyarV4\Repositories\SeoOverrideRepository;

final class SeoService
{
    public function __construct(private readonly App $app) {}

    public function defaults(array $overrides = []): array
    {
        $settings = $this->app->make(SettingsService::class);
        return array_merge([
            'title' => (string) $settings->get('site_name', godyar_v4_config('seo.default_title', 'Godyar')),
            'description' => (string) $settings->get('site_tagline', godyar_v4_config('seo.default_description', '')),
            'robots' => godyar_v4_config('seo.robots', 'index,follow'),
            'canonical' => '',
            'og_image' => godyar_v4_config('seo.default_og_image', ''),
            'og_type' => 'website',
            'twitter_card' => 'summary_large_image',
            'json_ld' => [],
            'override' => null,
        ], $overrides);
    }

    public function forPage(array $page, string $locale): array
    {
        return $this->apply('page', $locale, $page, $this->defaults([
            'title' => (string) ($page['title'] ?? 'Page'),
            'description' => strip_tags((string) ($page['excerpt'] ?? $page['content'] ?? '')),
            'canonical' => godyar_v4_url($locale, 'page/' . ($page['slug'] ?? '')),
            'json_ld' => ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => (string) ($page['title'] ?? ''), 'url' => godyar_v4_url($locale, 'page/' . ($page['slug'] ?? ''))],
        ]));
    }

    public function forNews(array $news, string $locale): array
    {
        return $this->apply('news', $locale, $news, $this->defaults([
            'title' => (string) ($news['title'] ?? 'News'),
            'description' => strip_tags((string) ($news['excerpt'] ?? '')),
            'canonical' => godyar_v4_url($locale, 'news/' . ($news['slug'] ?? '')),
            'og_type' => 'article',
            'og_image' => (string) ($news['image'] ?? ''),
            'json_ld' => ['@context' => 'https://schema.org', '@type' => 'NewsArticle', 'headline' => (string) ($news['title'] ?? ''), 'datePublished' => (string) ($news['published_at'] ?? date('c')), 'dateModified' => (string) ($news['updated_at'] ?? $news['published_at'] ?? date('c')), 'description' => strip_tags((string) ($news['excerpt'] ?? '')), 'mainEntityOfPage' => godyar_v4_url($locale, 'news/' . ($news['slug'] ?? ''))],
        ]));
    }

    public function forCategory(array $category, string $locale): array
    {
        return $this->apply('category', $locale, $category, $this->defaults([
            'title' => (string) ($category['name'] ?? 'Category'),
            'description' => strip_tags((string) ($category['description'] ?? '')),
            'canonical' => godyar_v4_url($locale, 'category/' . ($category['slug'] ?? '')),
            'json_ld' => ['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => (string) ($category['name'] ?? ''), 'url' => godyar_v4_url($locale, 'category/' . ($category['slug'] ?? ''))],
        ]));
    }

    public function preview(string $type, string $locale, string $identifier = ''): array
    {
        $type = strtolower(trim($type)); $identifier = trim($identifier); $record = null; $meta = $this->defaults(['title'=>'SEO Preview Center','description'=>'لم يتم العثور على العنصر المطلوب.']);
        if ($type === 'news') { $repo = $this->app->make(NewsRepository::class); $record = ctype_digit($identifier) ? $repo->findById((int)$identifier, $locale) : $repo->findBySlug($identifier, $locale); if ($record) $meta = $this->forNews($record, $locale); }
        if ($type === 'page') { $repo = $this->app->make(PageRepository::class); $record = ctype_digit($identifier) ? $repo->findById((int)$identifier, $locale) : $repo->findBySlug($identifier, $locale); if ($record) $meta = $this->forPage($record, $locale); }
        if ($type === 'category') { $repo = $this->app->make(CategoryRepository::class); $record = ctype_digit($identifier) ? $repo->findById((int)$identifier, $locale) : $repo->findBySlug($identifier, $locale); if ($record) $meta = $this->forCategory($record, $locale); }
        return ['type'=>$type,'identifier'=>$identifier,'record'=>$record,'meta'=>$meta,'checks'=>$this->checks($meta)];
    }

    private function apply(string $type, string $locale, array $record, array $meta): array
    {
        $override = $this->app->make(SeoOverrideRepository::class)->findFor($type, $locale, $record);
        if ($override) {
            foreach (['title','description','canonical','og_image','robots'] as $key) { if (!empty($override[$key])) $meta[$key] = $override[$key]; }
            $meta['override'] = $override;
        }
        return $meta;
    }

    private function checks(array $meta): array
    {
        return [
            ['label'=>'Title','status'=>mb_strlen(trim((string)($meta['title'] ?? ''))) >= 30 ? 'ok' : 'warn','message'=>'طول العنوان: ' . mb_strlen(trim((string)($meta['title'] ?? '')))],
            ['label'=>'Description','status'=>mb_strlen(trim((string)($meta['description'] ?? ''))) >= 70 ? 'ok' : 'warn','message'=>'طول الوصف: ' . mb_strlen(trim((string)($meta['description'] ?? '')))],
            ['label'=>'Canonical','status'=>!empty($meta['canonical']) ? 'ok' : 'warn','message'=>!empty($meta['canonical']) ? 'canonical موجود' : 'canonical مفقود'],
            ['label'=>'Overrides','status'=>!empty($meta['override']) ? 'ok' : 'warn','message'=>!empty($meta['override']) ? 'يوجد override محفوظ' : 'لا يوجد override مخصص'],
        ];
    }
}
