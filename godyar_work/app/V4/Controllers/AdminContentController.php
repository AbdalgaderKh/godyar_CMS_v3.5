<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\AuthorRepository;
use GodyarV4\Repositories\MediaRepository;
use GodyarV4\Repositories\NewsRepository;
use GodyarV4\Repositories\RevisionRepository;
use GodyarV4\Repositories\TagRepository;
use GodyarV4\Services\MediaManagerService;

final class AdminContentController extends Controller
{
    public function authors(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        $items = $this->app->make(AuthorRepository::class)->all(120);
        return $this->theme()->render('pages/admin-authors', [
            'locale' => 'ar',
            'items' => $items,
            'flash' => godyar_v4_flash_get('authors'),
            'seo' => $this->seo()->defaults([
                'title' => 'V4 Authors Manager',
                'description' => 'إدارة الكتّاب المرتبطين بطبقة v4.',
                'canonical' => godyar_v4_base_url('/v4/admin/authors'),
                'robots' => 'noindex,nofollow',
            ]),
        ]);
    }

    public function createAuthor(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        if (!godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            godyar_v4_flash_set('authors', 'فشل التحقق الأمني.', 'danger');
            return Response::redirect(godyar_v4_base_url('/v4/admin/authors'));
        }
        $name = trim((string)$request->input('name', ''));
        if ($name === '') {
            godyar_v4_flash_set('authors', 'اسم الكاتب مطلوب.', 'warning');
            return Response::redirect(godyar_v4_base_url('/v4/admin/authors'));
        }
        $this->app->make(AuthorRepository::class)->create([
            'name' => $name,
            'slug' => (string)$request->input('slug', ''),
            'bio' => (string)$request->input('bio', ''),
            'avatar' => (string)$request->input('avatar', ''),
        ]);
        godyar_v4_flash_set('authors', 'تمت إضافة الكاتب داخل طبقة v4.', 'success');
        return Response::redirect(godyar_v4_base_url('/v4/admin/authors'));
    }

    public function updateAuthor(Request $request, string $slug): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        if (!godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            godyar_v4_flash_set('authors', 'فشل التحقق الأمني.', 'danger');
            return Response::redirect(godyar_v4_base_url('/v4/admin/authors'));
        }
        $updated = $this->app->make(AuthorRepository::class)->updateBySlug($slug, [
            'name' => (string)$request->input('name', ''),
            'bio' => (string)$request->input('bio', ''),
            'avatar' => (string)$request->input('avatar', ''),
        ]);
        godyar_v4_flash_set('authors', $updated ? 'تم تحديث الكاتب.' : 'التحديث متاح للعناصر التي تم إنشاؤها داخل طبقة v4 حاليًا.', $updated ? 'success' : 'warning');
        return Response::redirect(godyar_v4_base_url('/v4/admin/authors'));
    }

    public function deleteAuthor(Request $request, string $slug): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        if (!godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            godyar_v4_flash_set('authors', 'فشل التحقق الأمني.', 'danger');
            return Response::redirect(godyar_v4_base_url('/v4/admin/authors'));
        }
        $deleted = $this->app->make(AuthorRepository::class)->deleteBySlug($slug);
        godyar_v4_flash_set('authors', $deleted ? 'تم حذف الكاتب من طبقة v4.' : 'الحذف متاح فقط للعناصر المخزنة في طبقة v4 حاليًا.', $deleted ? 'success' : 'warning');
        return Response::redirect(godyar_v4_base_url('/v4/admin/authors'));
    }

    public function tags(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        $items = $this->app->make(TagRepository::class)->all(200);
        return $this->theme()->render('pages/admin-tags', [
            'locale' => 'ar',
            'items' => $items,
            'flash' => godyar_v4_flash_get('tags'),
            'seo' => $this->seo()->defaults([
                'title' => 'V4 Tags Manager',
                'description' => 'إدارة الوسوم المرتبطة بطبقة v4.',
                'canonical' => godyar_v4_base_url('/v4/admin/tags'),
                'robots' => 'noindex,nofollow',
            ]),
        ]);
    }

    public function createTag(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        if (!godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            godyar_v4_flash_set('tags', 'فشل التحقق الأمني.', 'danger');
            return Response::redirect(godyar_v4_base_url('/v4/admin/tags'));
        }
        $name = trim((string)$request->input('name', ''));
        if ($name === '') {
            godyar_v4_flash_set('tags', 'اسم الوسم مطلوب.', 'warning');
            return Response::redirect(godyar_v4_base_url('/v4/admin/tags'));
        }
        $this->app->make(TagRepository::class)->create([
            'name' => $name,
            'slug' => (string)$request->input('slug', ''),
        ]);
        godyar_v4_flash_set('tags', 'تمت إضافة الوسم.', 'success');
        return Response::redirect(godyar_v4_base_url('/v4/admin/tags'));
    }

    public function updateTag(Request $request, string $slug): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        if (!godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            godyar_v4_flash_set('tags', 'فشل التحقق الأمني.', 'danger');
            return Response::redirect(godyar_v4_base_url('/v4/admin/tags'));
        }
        $updated = $this->app->make(TagRepository::class)->updateBySlug($slug, [
            'name' => (string)$request->input('name', ''),
        ]);
        godyar_v4_flash_set('tags', $updated ? 'تم تحديث الوسم.' : 'التحديث متاح للعناصر التي أُنشئت داخل طبقة v4 حاليًا.', $updated ? 'success' : 'warning');
        return Response::redirect(godyar_v4_base_url('/v4/admin/tags'));
    }

    public function deleteTag(Request $request, string $slug): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        if (!godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            godyar_v4_flash_set('tags', 'فشل التحقق الأمني.', 'danger');
            return Response::redirect(godyar_v4_base_url('/v4/admin/tags'));
        }
        $deleted = $this->app->make(TagRepository::class)->deleteBySlug($slug);
        godyar_v4_flash_set('tags', $deleted ? 'تم حذف الوسم.' : 'الحذف متاح للعناصر المخزنة في طبقة v4 حاليًا.', $deleted ? 'success' : 'warning');
        return Response::redirect(godyar_v4_base_url('/v4/admin/tags'));
    }

    public function revisions(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        $newsId = (int)($request->query('news_id') ?? 0);
        $leftId = (string)($request->query('left') ?? '');
        $rightId = (string)($request->query('right') ?? '');

        $news = null;
        $revisions = [];
        $comparison = ['left' => null, 'right' => null, 'fields' => []];

        if ($newsId > 0) {
            $news = $this->app->make(NewsRepository::class)->findById($newsId) ?? ['id' => $newsId, 'title' => 'خبر #' . $newsId];
            $repo = $this->app->make(RevisionRepository::class);
            $revisions = $repo->forNews($newsId, 20);
            if ($leftId !== '' && $rightId !== '') {
                $comparison = $repo->compareNews($newsId, $leftId, $rightId);
            } elseif (count($revisions) >= 2) {
                $comparison = $repo->compareNews($newsId, (string)$revisions[0]['id'], (string)$revisions[1]['id']);
            }
        }

        return $this->theme()->render('pages/admin-revisions', [
            'locale' => 'ar',
            'news' => $news,
            'newsId' => $newsId,
            'items' => $revisions,
            'comparison' => $comparison,
            'flash' => godyar_v4_flash_get('revisions'),
            'seo' => $this->seo()->defaults([
                'title' => 'V4 Revision Compare',
                'description' => 'مقارنة المراجعات ضمن طبقة v4.',
                'canonical' => godyar_v4_base_url('/v4/admin/revisions'),
                'robots' => 'noindex,nofollow',
            ]),
        ]);
    }

    public function restoreRevision(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        if (!godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            godyar_v4_flash_set('revisions', 'فشل التحقق الأمني.', 'danger');
            return Response::redirect(godyar_v4_base_url('/v4/admin/revisions?news_id=' . (int)$request->input('news_id', 0)));
        }
        $newsId = (int)$request->input('news_id', 0);
        $revisionId = trim((string)$request->input('revision_id', ''));
        $actor = (string)((godyar_v4_admin_user()['email'] ?? godyar_v4_admin_user()['name'] ?? 'admin'));
        $result = $this->app->make(RevisionRepository::class)->restoreNewsToDraft($newsId, $revisionId, $actor);
        godyar_v4_flash_set('revisions', (string)$result['message'], ($result['ok'] ?? false) ? 'success' : 'danger');
        return Response::redirect(godyar_v4_base_url('/v4/admin/revisions?news_id=' . $newsId));
    }

    public function mediaUsage(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        $usage = $this->app->make(MediaRepository::class)->usageMap(220);
        return $this->theme()->render('pages/admin-media-usage', [
            'locale' => 'ar',
            'usage' => $usage,
            'seo' => $this->seo()->defaults([
                'title' => 'V4 Media Usage Map',
                'description' => 'خريطة استخدام الوسائط لاكتشاف الملفات المستخدمة وغير المستخدمة.',
                'canonical' => godyar_v4_base_url('/v4/admin/media-usage'),
                'robots' => 'noindex,nofollow',
            ]),
        ]);
    }

    public function seoPreview(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        $type = (string)($request->query('type') ?? 'news');
        $identifier = trim((string)($request->query('id') ?? $request->query('slug') ?? ''));
        $locale = (string)($request->query('lang') ?? 'ar');
        $preview = $this->seo()->preview($type, $locale, $identifier);
        return $this->theme()->render('pages/admin-seo-preview', [
            'locale' => 'ar',
            'preview' => $preview,
            'seo' => $this->seo()->defaults([
                'title' => 'V4 SEO Preview Center',
                'description' => 'معاينة وتدقيق title/description/canonical/OG قبل النشر.',
                'canonical' => godyar_v4_base_url('/v4/admin/seo-preview'),
                'robots' => 'noindex,nofollow',
            ]),
        ]);
    }

    public function media(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        $items = $this->app->make(MediaRepository::class)->recent(180);
        return $this->theme()->render('pages/admin-media', [
            'locale' => 'ar',
            'items' => $items,
            'flash' => godyar_v4_flash_get('media'),
            'seo' => $this->seo()->defaults([
                'title' => 'V4 Media Manager',
                'description' => 'إدارة الوسائط والرفع والتحويل التلقائي ضمن طبقة v4.',
                'canonical' => godyar_v4_base_url('/v4/admin/media'),
                'robots' => 'noindex,nofollow',
            ]),
        ]);
    }

    public function uploadMedia(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) { return $guard; }
        if (!godyar_v4_csrf_valid((string)$request->input('_token', ''))) {
            godyar_v4_flash_set('media', 'فشل التحقق الأمني.', 'danger');
            return Response::redirect(godyar_v4_base_url('/v4/admin/media'));
        }
        $file = $request->files('media_file');
        if (!is_array($file)) {
            godyar_v4_flash_set('media', 'لم يتم اختيار ملف.', 'warning');
            return Response::redirect(godyar_v4_base_url('/v4/admin/media'));
        }
        $result = $this->app->make(MediaManagerService::class)->storeUploadedFile($file, 'media');
        if (!($result['ok'] ?? false)) {
            godyar_v4_flash_set('media', (string)($result['message'] ?? 'فشل رفع الملف.'), 'danger');
            return Response::redirect(godyar_v4_base_url('/v4/admin/media'));
        }
        $absolute = godyar_v4_project_root() . '/' . ltrim((string)$result['path'], '/');
        $mime = (string)@mime_content_type($absolute);
        $size = @filesize($absolute) ?: 0;
        $this->app->make(MediaRepository::class)->registerUpload([
            'path' => (string)$result['path'],
            'alt_text' => (string)$request->input('alt_text', ''),
            'mime_type' => $mime,
            'size_kb' => (int)ceil($size / 1024),
        ]);
        godyar_v4_flash_set('media', 'تم رفع الوسيط بنجاح مع إنشاء المشتقات المتاحة.', 'success');
        return Response::redirect(godyar_v4_base_url('/v4/admin/media'));
    }
}
