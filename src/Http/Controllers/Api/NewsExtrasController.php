<?php
namespace App\Http\Controllers\Api;

use PDO;
use Throwable;

final class NewsExtrasController
{
    public function __construct(
        private PDO $pdo,
        private object $news,
        private object $tags,
        private object $categories
    ) {}

    private function json(array $payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }

    public function capabilities(): void
    {
        $this->json([
            'ok' => true,
            'capabilities' => [
                'bookmarks' => true,
                'reactions' => true,
                'poll' => true,
                'questions' => true,
                'tts' => true,
                'search_suggest' => true,
                'push' => true,
            ],
            'version' => 'NewsExtrasController safe 2026-01-14',
        ]);
    }

    public function latest(): void
    {
        try {
            $items = method_exists($this->news, 'latest') ? $this->news->latest(12) : [];
            $this->json(['ok' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            error_log('[NewsExtrasController] latest: ' . $e->getMessage());
            $this->json(['ok' => false, 'message' => 'تعذر جلب آخر الأخبار'], 500);
        }
    }

    public function suggest(): void
    {
        $q = trim((string)($_GET['q'] ?? $_GET['term'] ?? ''));
        if ($q === '') {
            $this->json(['ok' => true, 'items' => []]);
            return;
        }

        try {
            
            if (method_exists($this->news, 'search')) {
                $res = $this->news->search($q, 1, 8, ['type' => 'news', 'match' => 'any']);
                $items = $res['items'] ?? [];
                $this->json(['ok' => true, 'items' => $items]);
                return;
            }

            
            $like = '%' .str_replace(['%','_'], ['\%','\_'], $q) . '%';
            $st = $this->pdo->prepare("SELECT id, title FROM news WHERE title LIKE :q ORDER BY id DESC LIMIT 8");
            $st->execute([':q' => $like]);
            $items = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $this->json(['ok' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            error_log('[NewsExtrasController] suggest: ' . $e->getMessage());
            $this->json(['ok' => false, 'message' => 'تعذر الاقتراحات'], 500);
        }
    }

    
    
    
    public function bookmarksList(): void
    {
        $list = [];
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) gdy_session_start();
            $list = (array)($_SESSION['bookmarks'] ?? []);
        } catch (Throwable) {}

        $this->json(['ok' => true, 'items' => array_values($list)]);
    }

    public function bookmarkStatus(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $ok = false;
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) gdy_session_start();
            $list = (array)($_SESSION['bookmarks'] ?? []);
            $ok = in_array($id, $list, true);
        } catch (Throwable) {}
        $this->json(['ok' => true, 'bookmarked' => $ok]);
    }

    public function bookmarksToggle(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'message' => 'id غير صحيح'], 422);
            return;
        }

        try {
            if (session_status() !== PHP_SESSION_ACTIVE) gdy_session_start();
            $list = (array)($_SESSION['bookmarks'] ?? []);
            $list = array_values(array_map('intval', $list));

            if (in_array($id, $list, true)) {
                $list = array_values(array_filter($list, static fn($x) => (int)$x !== $id));
                $_SESSION['bookmarks'] = $list;
                $this->json(['ok' => true, 'bookmarked' => false]);
                return;
            }

            $list[] = $id;
            $list = array_values(array_unique($list));
            $_SESSION['bookmarks'] = $list;
            $this->json(['ok' => true, 'bookmarked' => true]);
        } catch (\Throwable $e) {
            error_log('[NewsExtrasController] bookmarksToggle: ' . $e->getMessage());
            $this->json(['ok' => false, 'message' => 'تعذر تحديث المفضلة'], 500);
        }
    }

    public function bookmarksImport(): void
    {
        $this->json(['ok' => true, 'message' => 'OK (no-op)']);
    }

    
    
    
    public function reactions(): void { $this->json(['ok' => true, 'items' => []]); }
    public function react(): void { $this->json(['ok' => true]); }

    public function poll(): void { $this->json(['ok' => true, 'poll' => null]); }
    public function pollVote(): void { $this->json(['ok' => true]); }

    public function questions(): void { $this->json(['ok' => true, 'items' => []]); }
    public function ask(): void { $this->json(['ok' => true]); }

    public function tts(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $lang = (string)($_GET['lang'] ?? 'ar');
        $format = strtolower((string)($_GET['format'] ?? 'mp3'));
        $rate = (float)($_GET['rate'] ?? 1.0);
        $download = (int)($_GET['download'] ?? 1) === 1;

        if ($id <= 0) {
            $this->json(['ok' => false, 'message' => 'Missing id'], 400);
            return;
        }

        try {
            $news = $this->news->getNewsById($id);
            if (!is_array($news)) {
                $this->json(['ok' => false, 'message' => 'Not found'], 404);
                return;
            }

            
            $title = (string)($news['title'] ?? $news['headline'] ?? '');
            $rawBody = (string)($news['content'] ?? $news['body'] ?? $news['description'] ?? $news['details'] ?? '');
            $rawBody = html_entity_decode($rawBody, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $rawBody = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $rawBody) ?? $rawBody;
            $rawBody = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $rawBody) ?? $rawBody;
            $text = trim(preg_replace('/\s+/u', ' ', strip_tags($rawBody)) ?? '');
            if ($title && stripos($text, $title) !== 0) { $text = trim($title . "\n\n" . $text); }

            if ($text === '') {
                $this->json(['ok' => false, 'message' => 'Empty text'], 422);
                return;
            }

            
            $base = defined('ABSPATH') ? ABSPATH : (__DIR__ . '/../../../..');
            $cacheDir = rtrim($base, '/\\') . '/storage/cache/tts';
            if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0755, true); }

            
            $rate = max(0.25, min(4.0, $rate));
            $ext = ($format === 'wav') ? 'wav' : 'mp3';
            $hash = substr(sha1($id . '|' . $lang . '|' . $rate . '|' . $ext . '|' . $text), 0, 16);
            $outFile = $cacheDir . '/news_' . $id . '_' . $hash . '.' . $ext;

            if (!is_file($outFile) || filesize($outFile) < 1024) {
                
                $apiKey = (string)(getenv('GOOGLE_TTS_API_KEY') ?: '');
                if ($apiKey !== '') {
                    $langCode = $this->mapTtsLang($lang);
                    $audioEncoding = ($ext === 'wav') ? 'LINEAR16' : 'MP3';

                    $payload = [
                        'input' => ['text' => mb_substr($text, 0, 4500, 'UTF-8')], 
                        'voice' => [
                            'languageCode' => $langCode,
                            'ssmlGender' => 'NEUTRAL',
                        ],
                        'audioConfig' => [
                            'audioEncoding' => $audioEncoding,
                            'speakingRate' => $rate,
                        ],
                    ];

                    $resp = $this->httpJson(
                        'https://texttospeech.googleapis.com/v1/text:synthesize?key=' . rawurlencode($apiKey),
                        $payload
                    );

                    $audioB64 = (string)($resp['audioContent'] ?? '');
                    if ($audioB64 === '') {
                        $this->json(['ok' => false, 'message' => 'TTS failed (no audioContent)', 'details' => $resp], 502);
                        return;
                    }

                    $bin = base64_decode($audioB64, true);
                    if ($bin === false || strlen($bin) < 1024) {
                        $this->json(['ok' => false, 'message' => 'TTS failed (invalid audio)', 'details' => $resp], 502);
                        return;
                    }

                    @file_put_contents($outFile, $bin, LOCK_EX);
                } else {
                    
                    $allowExec = getenv('GDY_ALLOW_EXEC');
                    $allowExec = $allowExec === false ? '0' : (string)$allowExec;
                    $allowExec = !in_array(strtolower($allowExec), ['0','false','off','no'], true);

                    if (!$allowExec || !function_exists('shell_exec')) {
                        $this->json([
                            'ok' => false,
                            'message' => 'TTS requires GOOGLE_TTS_API_KEY (recommended) OR enable GDY_ALLOW_EXEC=1 + shell_exec',
                        ], 501);
                        return;
                    }

                    
                    $this->json(['ok' => false, 'message' => 'Legacy TTS engine removed in this build. Use GOOGLE_TTS_API_KEY instead.'], 501);
                    return;
                }
            }

            if (!$download) {
                $url = '/api/news/tts?id=' . $id . '&lang=' . rawurlencode($lang) . '&format=' . rawurlencode($ext) . '&rate=' . rawurlencode((string)$rate) . '&download=1';
                $this->json(['ok' => true, 'cached' => true, 'download_url' => $url]);
                return;
            }

            
            $mime = ($ext === 'wav') ? 'audio/wav' : 'audio/mpeg';
            header('Content-Type: ' . $mime);
            header('Content-Disposition: attachment; filename="news_' . $id . '.' . $ext . '"');
            header('Content-Length: ' . (string)filesize($outFile));
            header('Cache-Control: public, max-age=31536000');
            readfile($outFile);
            return;

        } catch (Throwable $e) {
            error_log('[NewsExtrasController] TTS error: ' . $e->getMessage());
            $this->json(['ok' => false, 'message' => 'TTS error'], 500);
        }
    }

    public function pdf(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $lang = (string)($_GET['lang'] ?? 'ar');
        if ($id <= 0) { $this->json(['ok' => false, 'message' => 'Missing id'], 400); return; }

        $allowExec = getenv('GDY_ALLOW_EXEC');
        $allowExec = $allowExec === false ? '0' : (string)$allowExec;
        $allowExec = !in_array(strtolower($allowExec), ['0','false','off','no'], true);

        if (!$allowExec || !function_exists('shell_exec')) {
            $this->json(['ok' => false, 'message' => 'PDF generation requires GDY_ALLOW_EXEC=1 and shell_exec enabled'], 501);
            return;
        }

        try {
            $news = $this->news->getNewsById($id);
            if (!is_array($news)) { $this->json(['ok' => false, 'message' => 'Not found'], 404); return; }

            $title = (string)($news['title'] ?? $news['headline'] ?? '');
            $body  = (string)($news['content'] ?? $news['details'] ?? $news['body'] ?? '');
            $cover = (string)($news['image'] ?? $news['cover'] ?? $news['image_url'] ?? '');

            $base = defined('ABSPATH') ? ABSPATH : (__DIR__ . '/../../../..');
            $cacheDir = rtrim($base, '/\\') . '/storage/cache/pdf';
            if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0755, true); }

            $hash = substr(sha1($id . '|' . $lang . '|' . $title . '|' . $body), 0, 16);
            $pdfFile = $cacheDir . "/news_{$id}_{$lang}_{$hash}.pdf";
            if (!is_file($pdfFile) || filesize($pdfFile) < 2048) {
                $html = $this->renderPdfHtml($title, $body, $cover, $lang);
                $tmpHtml = tempnam(sys_get_temp_dir(), 'gdy_pdf_') . '.html';
                file_put_contents($tmpHtml, $html);

                $tmpPdf = tempnam(sys_get_temp_dir(), 'gdy_pdf_') . '.pdf';

                $has = static function(string $cmd): bool {
                    $p = trim((string)shell_exec('command -v ' . escapeshellarg($cmd) . ' 2>/dev/null'));
                    return $p !== '';
                };

                $ok = false;
                if ($has('wkhtmltopdf')) {
                    $cmd = 'wkhtmltopdf --quiet --encoding utf-8 --page-size A4 --margin-top 12mm --margin-bottom 14mm --margin-left 12mm --margin-right 12mm ' .
                        escapeshellarg($tmpHtml) . ' ' . escapeshellarg($tmpPdf);
                    shell_exec($cmd . ' 2>/dev/null');
                    $ok = is_file($tmpPdf) && filesize($tmpPdf) > 2048;
                } elseif ($has('google-chrome') || $has('chromium') || $has('chromium-browser')) {
                    $bin = $has('google-chrome') ? 'google-chrome' : ($has('chromium') ? 'chromium' : 'chromium-browser');
                    $cmd = $bin . ' --headless --disable-gpu --no-sandbox --print-to-pdf=' . escapeshellarg($tmpPdf) . ' ' . escapeshellarg('file://' . $tmpHtml);
                    shell_exec($cmd . ' 2>/dev/null');
                    $ok = is_file($tmpPdf) && filesize($tmpPdf) > 2048;
                }

                @unlink($tmpHtml);

                if (!$ok) {
                    @unlink($tmpPdf);
                    $this->json(['ok' => false, 'message' => 'No PDF engine found. Install wkhtmltopdf or headless chrome, and enable GDY_ALLOW_EXEC=1'], 501);
                    return;
                }

                @rename($tmpPdf, $pdfFile);
            }

            if (!headers_sent()) {
                header('Content-Type: application/pdf');
                header('Content-Length: ' . (string)filesize($pdfFile));
                $safe = preg_replace('/[^a-zA-Z0-9_\-\.]+/', '_', "news_{$id}.pdf");
                header('Content-Disposition: attachment; filename="' . $safe . '"');
                header('Cache-Control: public, max-age=31536000, immutable');
            }
            readfile($pdfFile);
        } catch (Throwable $e) {
            if (function_exists('gdy_log')) {
                gdy_log('error', 'PDF endpoint failed', ['id' => $id, 'err' => $e->getMessage()]);
            }
            $this->json(['ok' => false, 'message' => 'Server error'], 500);
        }
    }

    private function renderPdfHtml(string $title, string $body, string $cover, string $lang): string
    {
        $dir = ($lang === 'ar' || $lang === 'fa' || $lang === 'ur') ? 'rtl' : 'ltr';
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $bodyText  = trim($body);
        $bodyHtml = nl2br(htmlspecialchars(strip_tags($bodyText), ENT_QUOTES, 'UTF-8'));
        $coverHtml = '';
        if ($cover) {
            $safeCover = htmlspecialchars($cover, ENT_QUOTES, 'UTF-8');
            $coverHtml = '<div class="cover"><img src="' . $safeCover . '" alt=""     /></div>';
        }

        return '<!doctype html><html lang="' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '" dir="' . $dir . '"><head><meta charset="utf-8"     />
' .
            '<style>
                *{box-sizing:border-box}
                body{font-family: DejaVu Sans, Arial, sans-serif; color:#0f172a; margin:0; padding:0}
                .page{padding:24px}
                h1{font-size:20px; margin:0 0 12px 0}
                .meta{font-size:11px; color:#475569; margin-bottom:14px}
                .cover{margin:10px 0 16px 0}
                .cover img{max-width:100%; height:auto; border-radius:10px}
                .content{font-size:13px; line-height:1.85}
                .footer{margin-top:18px; font-size:10px; color:#64748b}
            </style></head><body><div class="page">' .
            '<h1>' . $safeTitle . '</h1>' .
            '<div class="meta">Godyar News</div>' .
            $coverHtml .
            '<div class="content">' . $bodyHtml . '</div>' .
            '<div class="footer">Generated by Godyar PDF</div>' .
            '</div></body></html>';
    }

    private function mapTtsLang(string $lang): string
    {
        $lang = strtolower(trim($lang));
        
        if (preg_match('/^[a-z]{2,3}-[A-Z]{2}$/', $lang)) { return $lang; }
        
        return match ($lang) {
            'ar', 'arabic' => 'ar-XA',
            'en', 'english' => 'en-US',
            'fr', 'french' => 'fr-FR',
            'tr', 'turkish' => 'tr-TR',
            'ur' => 'ur-IN',
            default => 'ar-XA',
        };
    }

    
    private function httpJson(string $url, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            return ['error' => 'json_encode_failed'];
        }

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
        ];

        
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);
            $res = curl_exec($ch);
            $http = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($res === false || $res === null) {
                return ['error' => 'curl_error', 'details' => $err, 'http_code' => $http];
            }
            $arr = json_decode((string)$res, true);
            if (!is_array($arr)) {
                return ['error' => 'invalid_json', 'http_code' => $http, 'raw' => (string)$res];
            }
            if ($http >= 400) {
                $arr['_http_code'] = $http;
            }
            return $arr;
        }

        
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 25,
            ],
        ]);
        $res = @file_get_contents($url, false, $ctx);
        if ($res === false) {
            return ['error' => 'http_failed'];
        }
        $arr = json_decode((string)$res, true);
        return is_array($arr) ? $arr : ['error' => 'invalid_json', 'raw' => (string)$res];
    }

function version(): string
    {
        return 'NewsExtrasController safe 2026-01-14';
    }
    public function pushSubscribe(): void
    {
        try {
            $raw = file_get_contents('php://input');
            $data = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $this->json(['ok' => false, 'message' => 'Invalid JSON'], 400);
                return;
            }

            $endpoint = trim((string)($data['endpoint'] ?? ''));
            $keys = is_array($data['keys'] ?? null) ? $data['keys'] : [];
            $p256dh = trim((string)($keys['p256dh'] ?? ''));
            $auth = trim((string)($keys['auth'] ?? ''));

            if ($endpoint === '' || $p256dh === '' || $auth === '') {
                $this->json(['ok' => false, 'message' => 'Missing subscription fields'], 422);
                return;
            }

            
            if (function_exists('gdy_db_stmt_table_exists')) {
                if (!gdy_db_stmt_table_exists($this->pdo, 'push_subscriptions')) {
                    $drv = (function_exists('gdy_pdo_is_pgsql') && gdy_pdo_is_pgsql($this->pdo)) ? 'pgsql' : 'mysql';
                    if ($drv === 'pgsql') {
                        $sql = "CREATE TABLE IF NOT EXISTS push_subscriptions (
                            id SERIAL PRIMARY KEY,
                            endpoint TEXT NOT NULL UNIQUE,
                            p256dh TEXT NOT NULL,
                            auth TEXT NOT NULL,
                            content TEXT NULL,
                            created_at TIMESTAMP NULL,
                            updated_at TIMESTAMP NULL
                        )";
                    } else {
                        $sql = "CREATE TABLE IF NOT EXISTS push_subscriptions (
                            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            endpoint TEXT NOT NULL,
                            p256dh TEXT NOT NULL,
                            auth TEXT NOT NULL,
                            content LONGTEXT NULL,
                            created_at DATETIME NULL,
                            updated_at DATETIME NULL,
                            UNIQUE KEY uq_endpoint (endpoint(255))
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    }
                    $this->pdo->exec($sql);
                }
            }

            $now = date('Y-m-d H:i:s');
            $payload = [
                'endpoint' => $endpoint,
                'p256dh' => $p256dh,
                'auth' => $auth,
                'content' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
                'created_at' => $now,
            ];

            if (function_exists('gdy_db_upsert')) {
                gdy_db_upsert($this->pdo, 'push_subscriptions', $payload, ['endpoint'], ['p256dh','auth','content','updated_at']);
            } else {
                $st = $this->pdo->prepare("INSERT INTO push_subscriptions (endpoint,p256dh,auth,content,created_at,updated_at)
                    VALUES (:e,:p,:a,:c,:ca,:ua)
                    ON DUPLICATE KEY UPDATE p256dh=VALUES(p256dh), auth=VALUES(auth), content=VALUES(content), updated_at=VALUES(updated_at)");
                $st->execute([
                    ':e' => $endpoint,
                    ':p' => $p256dh,
                    ':a' => $auth,
                    ':c' => (string)$payload['content'],
                    ':ca' => $now,
                    ':ua' => $now,
                ]);
            }

            $this->json(['ok' => true]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => 'Push subscribe failed'], 500);
        }
    }

    public function pushUnsubscribe(): void
    {
        try {
            $raw = file_get_contents('php://input');
            $data = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($data)) {
                $this->json(['ok' => false, 'message' => 'Invalid JSON'], 400);
                return;
            }
            $endpoint = trim((string)($data['endpoint'] ?? ''));
            if ($endpoint === '') {
                $this->json(['ok' => false, 'message' => 'Missing endpoint'], 422);
                return;
            }

            $st = $this->pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = :e");
            $st->execute([':e' => $endpoint]);

            $this->json(['ok' => true]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'message' => 'Push unsubscribe failed'], 500);
        }
    }
}
