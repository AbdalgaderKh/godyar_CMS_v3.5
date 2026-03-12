<?php
declare(strict_types=1);
namespace GodyarV4\Repositories;

final class MediaCleanupQueueRepository
{
    private function file(): string { return godyar_v4_storage_path('v4/media_cleanup_queue.json'); }
    private function read(): array { $f = $this->file(); return is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : []; }
    private function write(array $rows): void { $f = $this->file(); if (!is_dir(dirname($f))) @mkdir(dirname($f), 0775, true); file_put_contents($f, json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); }
    public function all(): array { return $this->read(); }
    public function queuedPaths(): array { return array_values(array_filter(array_map(fn($r)=>(string)($r['path']??''), $this->read()))); }
    public function enqueue(string $path, string $actor='system'): void { $rows=$this->read(); foreach($rows as $r){ if(($r['path']??'')===$path) return; } $rows[]=['path'=>$path,'status'=>'queued','queued_at'=>date('c'),'queued_by'=>$actor,'trash_path'=>'']; $this->write($rows); }
    public function remove(string $path): void { $this->write(array_values(array_filter($this->read(), fn($r)=>(string)($r['path']??'')!==$path))); }
    public function archive(string $path, string $actor='system'): array
    {
        $absolute = godyar_v4_project_root() . '/' . ltrim($path, '/');
        if (!is_file($absolute)) return ['ok'=>false,'message'=>'الملف غير موجود على القرص.'];
        $trash = godyar_v4_storage_path('trash/media/' . date('Y-m-d'));
        if (!is_dir($trash)) @mkdir($trash, 0775, true);
        $target = $trash . '/' . basename($absolute);
        if (!@rename($absolute, $target)) return ['ok'=>false,'message'=>'تعذر نقل الملف إلى trash الآمن.'];
        $rows=$this->read(); $found=false;
        foreach($rows as &$r){ if(($r['path']??'')===$path){ $r['status']='archived'; $r['trash_path']=ltrim(str_replace(godyar_v4_project_root(), '', $target), '/'); $r['archived_at']=date('c'); $r['archived_by']=$actor; $found=true; break; } }
        unset($r); if(!$found) $rows[]=['path'=>$path,'status'=>'archived','queued_at'=>date('c'),'queued_by'=>$actor,'trash_path'=>ltrim(str_replace(godyar_v4_project_root(), '', $target), '/'),'archived_at'=>date('c'),'archived_by'=>$actor];
        $this->write($rows);
        return ['ok'=>true,'message'=>'تم نقل الملف إلى trash الآمن.'];
    }
    public function restoreArchive(string $path, string $actor='system'): array
    {
        $rows = $this->read();
        foreach($rows as &$row) {
            if (($row['path'] ?? '') !== $path || ($row['status'] ?? '') !== 'archived') continue;
            $trashAbs = godyar_v4_project_root() . '/' . ltrim((string)($row['trash_path'] ?? ''), '/');
            $targetAbs = godyar_v4_project_root() . '/' . ltrim($path, '/');
            if (!is_file($trashAbs)) return ['ok'=>false,'message'=>'نسخة trash غير موجودة.'];
            if (!is_dir(dirname($targetAbs))) @mkdir(dirname($targetAbs), 0775, true);
            if (!@rename($trashAbs, $targetAbs)) return ['ok'=>false,'message'=>'تعذر استعادة الملف من trash.'];
            $row['status'] = 'restored';
            $row['restored_at'] = date('c');
            $row['restored_by'] = $actor;
            $this->write($rows);
            return ['ok'=>true,'message'=>'تمت استعادة الملف من trash بنجاح.'];
        }
        unset($row);
        return ['ok'=>false,'message'=>'العنصر غير موجود داخل الأرشيف.'];
    }
}
