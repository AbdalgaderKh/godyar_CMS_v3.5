<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\MediaCleanupQueueRepository;
use GodyarV4\Repositories\MediaRepository;
use GodyarV4\Repositories\RedirectRepository;
use GodyarV4\Services\SeoAuditService;
use GodyarV4\Services\SystemHealthService;

final class AdminGovernanceController extends Controller
{
    public function redirects(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        return $this->theme()->render('pages/admin-redirects', [
            'locale'=>'ar',
            'rows'=>$this->app->make(RedirectRepository::class)->all(),
            'flash'=>godyar_v4_flash_get('redirects'),
            'seo'=>$this->seo()->defaults(['title'=>'V4 Redirect Manager','robots'=>'noindex,nofollow'])
        ]);
    }

    public function saveRedirect(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (!godyar_v4_csrf_valid((string)$request->input('_token',''))) return Response::redirect(godyar_v4_base_url('/v4/admin/redirects'));
        $old = trim((string)$request->input('old_path',''));
        $new = trim((string)$request->input('new_path',''));
        if ($old === '' || $new === '') {
            godyar_v4_flash_set('redirects','old_path و new_path مطلوبان.','danger');
            return Response::redirect(godyar_v4_base_url('/v4/admin/redirects'));
        }
        $this->app->make(RedirectRepository::class)->upsert([
            'id'=>(string)$request->input('id',''),
            'old_path'=>$old,
            'new_path'=>$new,
            'status_code'=>(int)$request->input('status_code',301),
            'is_active'=>$request->input('is_active') ? 1 : 0,
        ], (string)(godyar_v4_admin_user()['email'] ?? 'admin'));
        godyar_v4_flash_set('redirects','تم حفظ redirect بنجاح.','success');
        return Response::redirect(godyar_v4_base_url('/v4/admin/redirects'));
    }

    public function deleteRedirect(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (godyar_v4_csrf_valid((string)$request->input('_token',''))) {
            $this->app->make(RedirectRepository::class)->delete((string)$request->input('id',''));
            godyar_v4_flash_set('redirects','تم حذف redirect.','success');
        }
        return Response::redirect(godyar_v4_base_url('/v4/admin/redirects'));
    }

    public function seoAudit(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        $audit = $this->app->make(SeoAuditService::class)->run((string)($request->query('lang') ?? 'ar'));
        return $this->theme()->render('pages/admin-seo-audit', [
            'locale'=>'ar','audit'=>$audit,
            'seo'=>$this->seo()->defaults(['title'=>'V4 SEO Audit','robots'=>'noindex,nofollow'])
        ]);
    }

    public function mediaTrash(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        return $this->theme()->render('pages/admin-media-trash', [
            'locale'=>'ar',
            'rows'=>$this->app->make(MediaCleanupQueueRepository::class)->all(),
            'flash'=>godyar_v4_flash_get('media_trash'),
            'seo'=>$this->seo()->defaults(['title'=>'V4 Media Trash','robots'=>'noindex,nofollow'])
        ]);
    }

    public function restoreTrash(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (!godyar_v4_csrf_valid((string)$request->input('_token',''))) return Response::redirect(godyar_v4_base_url('/v4/admin/media-trash'));
        $res = $this->app->make(MediaCleanupQueueRepository::class)->restoreArchive((string)$request->input('path',''), (string)(godyar_v4_admin_user()['email'] ?? 'admin'));
        godyar_v4_flash_set('media_trash',(string)$res['message'], ($res['ok']??false) ? 'success' : 'danger');
        return Response::redirect(godyar_v4_base_url('/v4/admin/media-trash'));
    }

    public function systemHealth(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        return $this->theme()->render('pages/admin-system-health', [
            'locale'=>'ar',
            'report'=>$this->app->make(SystemHealthService::class)->report(),
            'duplicates'=>$this->app->make(MediaRepository::class)->duplicateMap(120),
            'brokenLinks'=>$this->app->make(MediaRepository::class)->brokenInternalLinks(60),
            'seo'=>$this->seo()->defaults(['title'=>'V4 System Health','robots'=>'noindex,nofollow'])
        ]);
    }
}
