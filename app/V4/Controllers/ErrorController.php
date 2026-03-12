<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;

final class ErrorController extends Controller
{
    public function notFound(Request $request): Response
    {
        return $this->theme()->render('pages/404', [
            'seo' => $this->seo()->defaults(['title' => '404']),
            'path' => $request->path(),
        ], 404);
    }
}
