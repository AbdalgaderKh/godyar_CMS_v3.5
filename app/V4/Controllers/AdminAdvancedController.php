<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;
use GodyarV4\Repositories\MediaCleanupQueueRepository;
use GodyarV4\Repositories\MediaRepository;
use GodyarV4\Repositories\RevisionRepository;
use GodyarV4\Repositories\SeoOverrideRepository;

final class AdminAdvancedController extends Controller
{
    public function revisions(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        $newsId = (int)($request->query('news_id') ?? 0);
        $items = $newsId > 0 ? $this->app->make(RevisionRepository::class)->forNews($newsId, 20) : [];
        return $this->theme()->render('pages/admin-revisions', ['locale'=>'ar','newsId'=>$newsId,'items'=>$items,'flash'=>godyar_v4_flash_get('revisions'),'seo'=>$this->seo()->defaults(['title'=>'V4 Revisions','robots'=>'noindex,nofollow'])]);
    }
    public function restoreDraft(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (!godyar_v4_csrf_valid((string)$request->input('_token',''))) return Response::redirect(godyar_v4_base_url('/v4/admin/revisions'));
        $res = $this->app->make(RevisionRepository::class)->restoreDraft((int)$request->input('news_id',0), (string)$request->input('revision_id',''), (string)(godyar_v4_admin_user()['email'] ?? 'admin'));
        godyar_v4_flash_set('revisions', (string)$res['message'], ($res['ok']??false)?'success':'danger');
        return Response::redirect(godyar_v4_base_url('/v4/admin/revisions?news_id='.(int)$request->input('news_id',0)));
    }
    public function restoreLive(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (!godyar_v4_csrf_valid((string)$request->input('_token',''))) return Response::redirect(godyar_v4_base_url('/v4/admin/revisions'));
        $res = $this->app->make(RevisionRepository::class)->restoreLive((int)$request->input('news_id',0), (string)$request->input('revision_id',''), (string)(godyar_v4_admin_user()['email'] ?? 'admin'));
        godyar_v4_flash_set('revisions', (string)$res['message'], ($res['ok']??false)?'success':'danger');
        return Response::redirect(godyar_v4_base_url('/v4/admin/revisions?news_id='.(int)$request->input('news_id',0)));
    }
    public function seoPreview(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        $preview = $this->seo()->preview((string)($request->query('type')??'news'), (string)($request->query('lang')??'ar'), (string)($request->query('slug')??''));
        return $this->theme()->render('pages/admin-seo-preview', ['locale'=>'ar','preview'=>$preview,'flash'=>godyar_v4_flash_get('seo_preview'),'seo'=>$this->seo()->defaults(['title'=>'V4 SEO Preview','robots'=>'noindex,nofollow'])]);
    }
    public function saveSeoOverride(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (!godyar_v4_csrf_valid((string)$request->input('_token',''))) return Response::redirect(godyar_v4_base_url('/v4/admin/seo-preview'));
        $type=(string)$request->input('type','news'); $lang=(string)$request->input('lang','ar'); $identifier=trim((string)$request->input('identifier',''));
        $this->app->make(SeoOverrideRepository::class)->upsert($type,$lang,$identifier,['title'=>(string)$request->input('title',''),'description'=>(string)$request->input('description',''),'canonical'=>(string)$request->input('canonical',''),'og_image'=>(string)$request->input('og_image',''),'robots'=>(string)$request->input('robots','')], (string)(godyar_v4_admin_user()['email'] ?? 'admin'));
        godyar_v4_flash_set('seo_preview','تم حفظ SEO override.','success');
        return Response::redirect(godyar_v4_base_url('/v4/admin/seo-preview?type='.urlencode($type).'&slug='.urlencode($identifier).'&lang='.urlencode($lang)));
    }
    public function mediaCleanup(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        return $this->theme()->render('pages/admin-media-cleanup', ['locale'=>'ar','usage'=>$this->app->make(MediaRepository::class)->usageMap(150),'queue'=>$this->app->make(MediaCleanupQueueRepository::class)->all(),'flash'=>godyar_v4_flash_get('media_cleanup'),'seo'=>$this->seo()->defaults(['title'=>'V4 Media Cleanup','robots'=>'noindex,nofollow'])]);
    }
    public function queueMedia(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (godyar_v4_csrf_valid((string)$request->input('_token',''))) { $this->app->make(MediaCleanupQueueRepository::class)->enqueue((string)$request->input('path',''), (string)(godyar_v4_admin_user()['email'] ?? 'admin')); godyar_v4_flash_set('media_cleanup','تمت إضافة الملف إلى cleanup queue.','success'); }
        return Response::redirect(godyar_v4_base_url('/v4/admin/media-cleanup'));
    }
    public function unqueueMedia(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (godyar_v4_csrf_valid((string)$request->input('_token',''))) { $this->app->make(MediaCleanupQueueRepository::class)->remove((string)$request->input('path','')); godyar_v4_flash_set('media_cleanup','تمت إزالة الملف من cleanup queue.','success'); }
        return Response::redirect(godyar_v4_base_url('/v4/admin/media-cleanup'));
    }
    public function archiveMedia(Request $request): Response
    {
        if ($guard = $this->ensureAdmin($request)) return $guard;
        if (!godyar_v4_csrf_valid((string)$request->input('_token',''))) return Response::redirect(godyar_v4_base_url('/v4/admin/media-cleanup'));
        $res = $this->app->make(MediaCleanupQueueRepository::class)->archive((string)$request->input('path',''), (string)(godyar_v4_admin_user()['email'] ?? 'admin'));
        godyar_v4_flash_set('media_cleanup',(string)$res['message'],($res['ok']??false)?'success':'danger');
        return Response::redirect(godyar_v4_base_url('/v4/admin/media-cleanup'));
    }
}
