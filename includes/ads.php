<?php

if (function_exists('h') === false) {
    function h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

function godyar_get_ads(string $position, int $limit = 1): array
{
    $pdo = gdy_pdo_safe();
    if (($pdo instanceof PDO) === false) {
        return [];
    }

    $limit = max(1, $limit);

    $sql = "
        SELECT *
        FROM ads
        WHERE position = :pos
          AND is_active = 1
          AND (start_at IS NULL OR start_at <= NOW())
          AND (end_at IS NULL OR end_at >= NOW())
          AND (max_impressions IS NULL OR impressions < max_impressions)
        ORDER BY id DESC
        LIMIT {$limit}
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pos' => $position]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (\Throwable $e) {
        error_log('[Godyar Ads] get_ads: ' . $e->getMessage());
        return [];
    }

    if (($rows === false)) {
        return [];
    }

    
    try {
        $ids = array_column($rows, 'id');
        if ((empty($ids) === false)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE ads SET impressions = impressions + 1 WHERE id IN ($in)";
            $stmt2 = $pdo->prepare($sql);
            $stmt2->execute($ids);
        }
    } catch (\Throwable $e) {
        error_log('[Godyar Ads] impressions update: ' . $e->getMessage());
    }

    return $rows;
}

function godyar_render_ad(string $position, int $limit = 1): void
{
    $ads = godyar_get_ads($position, $limit);
    if (($ads === false)) {
        return;
    }

    echo '<div class="godyar-ads godyar-ads-' .h($position) . '">';

    foreach ($ads as $ad) {
        $hasImg = !empty($ad['image_path']);
        $hasHtml = !empty($ad['html_code']);
        $url = $ad['target_url'] ?? '';

        echo '<div class="godyar-ad-item mb-3 text-center">';

        
        if ((empty($hasHtml) === false)) {
            echo '<div class="godyar-ad-html">';
            
            echo $ad['html_code'];
            echo '</div>';
        } elseif ((empty($hasImg) === false)) {
            
            $imgTag = '<img src="' .h($ad['image_path']) . '" alt="' .h($ad['title']) . '" class="img-fluid">';
            if ((empty($url) === false)) {
                echo '<a href="' .h($url) . '" target="_blank" rel="noopener nofollow" class="d-inline-block">';
                echo $imgTag;
                echo '</a>';
            } else {
                echo $imgTag;
            }
        } else {
            
            echo '<div class="godyar-ad-fallback small text-muted">' .h($ad['title']) . '</div>';
        }

        echo '</div>'; 
    }

    echo '</div>'; 
}
