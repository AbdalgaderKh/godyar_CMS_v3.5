<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Bootstrap\App;
use GodyarV4\Repositories\CategoryRepository;
use GodyarV4\Repositories\NewsRepository;
use GodyarV4\Repositories\PageRepository;
use GodyarV4\Repositories\SeoAuditRepository;

final class SeoAuditService
{
    public function __construct(private readonly App $app) {}

    public function run(string $locale = 'ar', int $limit = 30): array
    {
        $records = [];
        foreach (array_slice($this->app->make(NewsRepository::class)->all($locale, $limit), 0, $limit) as $news) {
            $records[] = $this->score('news', $locale, $news, $this->app->make(SeoService::class)->forNews($news, $locale));
        }
        foreach (array_slice($this->app->make(PageRepository::class)->all($locale, 10), 0, 10) as $page) {
            $record = !empty($page['slug']) ? $this->app->make(PageRepository::class)->findBySlug((string)$page['slug'], $locale) : $page;
            if (is_array($record)) $records[] = $this->score('page', $locale, $record, $this->app->make(SeoService::class)->forPage($record, $locale));
        }
        foreach (array_slice($this->app->make(CategoryRepository::class)->all($locale, 10), 0, 10) as $cat) {
            $record = !empty($cat['slug']) ? $this->app->make(CategoryRepository::class)->findBySlug((string)$cat['slug'], $locale) : $cat;
            if (is_array($record)) $records[] = $this->score('category', $locale, $record, $this->app->make(SeoService::class)->forCategory($record, $locale));
        }
        usort($records, fn($a,$b)=>($b['score'] <=> $a['score']));
        $summary = [
            'count' => count($records),
            'average' => count($records) ? (int)round(array_sum(array_column($records, 'score')) / count($records)) : 0,
            'excellent' => count(array_filter($records, fn($r)=>$r['score'] >= 90)),
            'needs_work' => count(array_filter($records, fn($r)=>$r['score'] < 70)),
        ];
        $this->app->make(SeoAuditRepository::class)->log(['summary'=>$summary]);
        return ['items'=>$records, 'summary'=>$summary];
    }

    private function score(string $type, string $locale, array $record, array $meta): array
    {
        $checks = [];
        $titleLen = mb_strlen(trim((string)($meta['title'] ?? '')));
        $descLen = mb_strlen(trim((string)($meta['description'] ?? '')));
        $content = strip_tags((string)($record['content'] ?? $record['excerpt'] ?? ''));
        $img = (string)($meta['og_image'] ?? $record['image'] ?? '');

        $checks[] = $this->mkCheck('Title', $titleLen >= 30 && $titleLen <= 60, 'طول العنوان '.$titleLen);
        $checks[] = $this->mkCheck('Description', $descLen >= 70 && $descLen <= 160, 'طول الوصف '.$descLen);
        $checks[] = $this->mkCheck('Canonical', !empty($meta['canonical']), !empty($meta['canonical']) ? 'canonical موجود' : 'canonical مفقود');
        $checks[] = $this->mkCheck('OG Image', !empty($img), !empty($img) ? 'صورة OG متوفرة' : 'لا توجد صورة OG');
        $checks[] = $this->mkCheck('Schema', !empty($meta['json_ld']), !empty($meta['json_ld']) ? 'JSON-LD موجود' : 'JSON-LD مفقود');
        $checks[] = $this->mkCheck('Content Length', mb_strlen($content) >= 120, 'عدد أحرف المحتوى '.mb_strlen($content));
        $score = 0;
        foreach ($checks as $check) { $score += $check['status'] === 'ok' ? 16 : ($check['status'] === 'warn' ? 8 : 0); }
        $score = min(100, $score);
        return [
            'type' => $type,
            'locale' => $locale,
            'id' => $record['id'] ?? '',
            'slug' => $record['slug'] ?? '',
            'title' => $record['title'] ?? $record['name'] ?? $record['slug'] ?? '',
            'url' => (string)($meta['canonical'] ?? ''),
            'score' => $score,
            'checks' => $checks,
        ];
    }
    private function mkCheck(string $label, bool $ok, string $message): array
    {
        return ['label'=>$label, 'status'=>$ok ? 'ok' : 'warn', 'message'=>$message];
    }
}
