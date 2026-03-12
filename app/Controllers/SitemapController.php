<?php

declare(strict_types=1);

namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;

final class SitemapController extends Controller
{
    public function index(Request $request): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (['/ar/', '/ar/page/about', '/ar/contact'] as $url) {
            $xml .= '<url><loc>' . e(godyar_v4_base_url($url)) . '</loc></url>';
        }
        $xml .= '</urlset>';
        return Response::xml($xml);
    }
}
