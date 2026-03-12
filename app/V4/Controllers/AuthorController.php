<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\AuthorRepository;

final class AuthorController extends Controller
{
    public function show(Request $request, string $locale, string $slug): Response
    {
        $repo = $this->app->make(AuthorRepository::class);
        $author = $repo->findBySlug($slug, $locale);
        if ($author === null) {
            return (new ErrorController($this->app))->notFound($request);
        }
        $articles = $repo->articlesByAuthor($slug, $locale, 12);
        return $this->theme()->render('pages/author', [
            'locale' => $locale,
            'author' => $author,
            'articles' => $articles,
            'seo' => $this->seo()->forGeneric(
                ($author['name'] ?? 'Author') . ' | Godyar',
                mb_substr((string)($author['bio'] ?? ''), 0, 160),
                godyar_v4_url($locale, 'author/' . $slug)
            ),
        ]);
    }
}
