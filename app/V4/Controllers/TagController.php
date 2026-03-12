<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\TagRepository;

final class TagController extends Controller
{
    public function show(Request $request, string $locale, string $slug): Response
    {
        $repo = $this->app->make(TagRepository::class);
        $tag = $repo->findBySlug($slug, $locale);
        if ($tag === null) {
            return (new ErrorController($this->app))->notFound($request);
        }
        $articles = $repo->articlesByTag($slug, $locale, 12);
        return $this->theme()->render('pages/tag', [
            'locale' => $locale,
            'tag' => $tag,
            'articles' => $articles,
            'seo' => $this->seo()->forGeneric(
                '#' . ($tag['name'] ?? 'tag') . ' | Godyar',
                'أرشيف الوسم ' . ($tag['name'] ?? ''),
                godyar_v4_url($locale, 'tag/' . $slug)
            ),
        ]);
    }
}
