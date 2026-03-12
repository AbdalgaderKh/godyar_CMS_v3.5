<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class MediaRepository
{
    public function usageMap(int $limit = 150): array
    {
        $items = $this->scanFilesystemMedia($limit);
        $queued = (new MediaCleanupQueueRepository())->queuedPaths();
        $newsRows = (new NewsRepository())->all('ar', 300);
        $pageRows = (new PageRepository())->all('ar', 100);
        $used = 0; $unused = 0;
        foreach ($items as &$item) {
            $needlePath = ltrim((string)($item['path'] ?? ''), '/');
            $needleBase = basename($needlePath);
            $refs = [];
            foreach ($newsRows as $news) {
                $blob = implode("
", [(string)($news['image'] ?? ''), (string)($news['content'] ?? ''), (string)($news['excerpt'] ?? '')]);
                if ($needlePath !== '' && (str_contains($blob, $needlePath) || ($needleBase !== '' && str_contains($blob, $needleBase)))) {
                    $refs[] = ['type'=>'news','title'=>(string)($news['title']??''),'url'=>!empty($news['slug'])?godyar_v4_url('ar','news/'.$news['slug']):''];
                }
            }
            foreach ($pageRows as $page) {
                $blob = implode("
", [(string)($page['image'] ?? ''), (string)($page['content'] ?? ''), (string)($page['excerpt'] ?? '')]);
                if ($needlePath !== '' && (str_contains($blob, $needlePath) || ($needleBase !== '' && str_contains($blob, $needleBase)))) {
                    $refs[] = ['type'=>'page','title'=>(string)($page['title']??''),'url'=>!empty($page['slug'])?godyar_v4_url('ar','page/'.$page['slug']):''];
                }
            }
            $item['references'] = $refs;
            $item['is_used'] = !empty($refs);
            $item['is_queued'] = in_array((string)($item['path'] ?? ''), $queued, true);
            if ($item['is_used']) $used++; else $unused++;
        }
        unset($item);
        return ['items'=>$items,'used_count'=>$used,'unused_count'=>$unused,'total_count'=>count($items)];
    }

    public function duplicateMap(int $limit = 120): array
    {
        $items = $this->scanFilesystemMedia($limit);
        $groups = [];
        foreach ($items as $item) {
            $abs = godyar_v4_project_root() . '/' . ltrim((string)$item['path'], '/');
            if (!is_file($abs)) continue;
            $hash = @md5_file($abs) ?: '';
            if ($hash === '') continue;
            $groups[$hash][] = array_merge($item, ['hash'=>$hash]);
        }
        $dupes = [];
        foreach ($groups as $hash => $group) {
            if (count($group) > 1) $dupes[] = ['hash'=>$hash, 'items'=>$group, 'count'=>count($group)];
        }
        usort($dupes, fn($a,$b)=>($b['count'] <=> $a['count']));
        return $dupes;
    }

    public function brokenInternalLinks(int $limit = 60): array
    {
        $bad = [];
        foreach ((new NewsRepository())->all('ar', 120) as $news) {
            foreach ($this->extractRelativeUrls((string)($news['content'] ?? '')) as $url) {
                if (!$this->internalUrlExists($url)) {
                    $bad[] = ['type'=>'news', 'title'=>(string)($news['title'] ?? ''), 'url'=>$url, 'source'=>!empty($news['slug']) ? godyar_v4_url('ar','news/'.$news['slug']) : ''];
                    if (count($bad) >= $limit) return $bad;
                }
            }
        }
        foreach ((new PageRepository())->all('ar', 60) as $page) {
            $record = !empty($page['slug']) ? (new PageRepository())->findBySlug((string)$page['slug'], 'ar') : $page;
            foreach ($this->extractRelativeUrls((string)($record['content'] ?? '')) as $url) {
                if (!$this->internalUrlExists($url)) {
                    $bad[] = ['type'=>'page', 'title'=>(string)($record['title'] ?? ''), 'url'=>$url, 'source'=>!empty($record['slug']) ? godyar_v4_url('ar','page/'.$record['slug']) : ''];
                    if (count($bad) >= $limit) return $bad;
                }
            }
        }
        return $bad;
    }

    private function extractRelativeUrls(string $html): array
    {
        if ($html === '') return [];
        preg_match_all("#(?:href|src)=[\"'](/[^\"']+)[\"']#i", $html, $m);
        return array_values(array_unique($m[1] ?? []));
    }
    private function internalUrlExists(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $root = godyar_v4_project_root();
        if ($path === '/' || $path === '') return true;
        if (is_file($root . '/' . ltrim($path, '/'))) return true;
        
        return (bool)preg_match('#^/(ar|en|fr)(/(news|page|category)/[^/]+|/contact|/?)$#', $path)
            || (bool)preg_match('#^/v4/admin/#', $path)
            || in_array($path, ['/sitemap.xml','/sitemap-pages.xml','/sitemap-news.xml','/sitemap-categories.xml'], true);
    }

    private function scanFilesystemMedia(int $limit): array
    {
        $roots = [godyar_v4_project_root() . '/uploads', godyar_v4_project_root() . '/assets/uploads', godyar_v4_project_root() . '/public/uploads', godyar_v4_project_root() . '/assets/images'];
        $items = [];
        foreach ($roots as $root) {
            if (!is_dir($root)) continue;
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (!$file->isFile()) continue;
                if (!preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $file->getFilename())) continue;
                $relative = ltrim(str_replace(godyar_v4_project_root(), '', $file->getPathname()), '/');
                $items[] = ['path'=>$relative,'url'=>godyar_v4_media_url($relative),'alt_text'=>pathinfo($file->getFilename(), PATHINFO_FILENAME), 'size'=>(int)$file->getSize()];
                if (count($items) >= $limit) break 2;
            }
        }
        return $items;
    }
}
