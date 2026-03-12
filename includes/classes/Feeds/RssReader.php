<?php
namespace Godyar\Feeds;

final class RssReader
{
    
    public static function read(string $url, int $limit = 20, int $timeout = 8): array
    {
        $url = trim($url);
        if ($url === '') {
            return [];
        }

        $xml = self::fetch($url, $timeout);
        if ($xml === null) {
            return [];
        }

        $data = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
        if (!$data) {
            return [];
        }

        $items = [];

        
        if (isset($data->channel->item)) {
            foreach ($data->channel->item as $item) {
                $items[] = [
                    'title' => (string)($item->title ?? ''),
                    'link' => (string)($item->link ?? ''),
                    'date' => isset($item->pubDate) ? (string)$item->pubDate : null,
                    'description' => isset($item->description) ? (string)$item->description : null,
                ];
                if (count($items) >= $limit) break;
            }
            return $items;
        }

        
        if (isset($data->entry)) {
            foreach ($data->entry as $entry) {
                $link = '';
                if (isset($entry->link)) {
                    foreach ($entry->link as $lnk) {
                        $attrs = $lnk->attributes();
                        if ($attrs && isset($attrs['href'])) {
                            $link = (string)$attrs['href'];
                            break;
                        }
                    }
                }

                $items[] = [
                    'title' => (string)($entry->title ?? ''),
                    'link' => $link,
                    'date' => isset($entry->updated) ? (string)$entry->updated : (isset($entry->published) ? (string)$entry->published : null),
                    'description' => isset($entry->summary) ? (string)$entry->summary : (isset($entry->content) ? (string)$entry->content : null),
                ];
                if (count($items) >= $limit) break;
            }
        }

        return $items;
    }

    private static function fetch(string $url, int $timeout): ?string
    {
        
        if (function_exists('gdy_http_get')) {
            try {
                $resp = gdy_http_get($url, $timeout);
                if (is_string($resp) && $resp !== '') return $resp;
            } catch (\Throwable $e) {
                
            }
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'user_agent' => 'GodyarRssReader/1.0',
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $ctx);
        return (is_string($raw) && $raw !== '') ? $raw : null;
    }
}
