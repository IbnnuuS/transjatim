<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobdeskTaskPhoto extends Model
{
    protected $table = 'jobdesk_task_photos';
    protected $fillable = ['task_id','path'];
    protected $appends  = ['url'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(JobdeskTask::class, 'task_id');
    }

    /**
     * Normalisasi path yang disimpan di DB.
     * - Hilangkan leading slash
     * - Buang prefix "public/" jika ada (konsisten relatif ke disk 'public')
     *   Contoh simpanan: jobdesk/2025/10/29/jb_xxx.jpg
     */
    public function setPathAttribute(?string $value): void
    {
        $v = ltrim((string) $value, '/');
        if (str_starts_with($v, 'public/')) {
            $v = substr($v, 7);
        }
        $this->attributes['path'] = $v;
    }

    /**
     * URL publik aman:
     * 1) MODE=auto  : pakai /storage/... jika symlink valid; kalau tidak, fallback ke /files/...
     * 2) MODE=files : paksa selalu lewat route files.show (berguna di Windows/Apache tanpa FollowSymLinks)
     *
     * .env:
     *   STORAGE_URL_MODE=auto   (default)
     *   STORAGE_URL_MODE=files  (paksa fallback)
     */
    public function getUrlAttribute(): string
    {
        $rel  = ltrim((string) $this->path, '/'); // jobdesk/2025/10/29/jb_xxx.jpg
        $mode = env('STORAGE_URL_MODE', 'auto');

        // Mode paksa lewat route fallback
        if ($mode === 'files') {
            return route('files.show', ['path' => $rel]);
        }

        // Mode auto: cek apakah file ada di /public/storage
        $publicFile = public_path('storage/'.$rel);
        if (is_file($publicFile)) {
            return asset('storage/'.$rel);
        }

        // Fallback aman: route /files/{path}
        return route('files.show', ['path' => $rel]);
    }
}
