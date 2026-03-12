<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Services\SitemapService;

final class SitemapController extends Controller
{
    public function index(Request $request): Response
    {
        return Response::xml($this->app->make(SitemapService::class)->indexXml());
    }

    public function pages(Request $request): Response
    {
        return Response::xml($this->app->make(SitemapService::class)->pagesXml());
    }

    public function news(Request $request): Response
    {
        return Response::xml($this->app->make(SitemapService::class)->newsXml());
    }

    public function categories(Request $request): Response
    {
        return Response::xml($this->app->make(SitemapService::class)->categoriesXml());
    }
}
