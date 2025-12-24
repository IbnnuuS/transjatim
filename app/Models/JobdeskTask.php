<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobdeskTask extends Model
{
    public const STATUSES = [
        'done',
        'in_progress',
        'pending',
        'cancelled',
        'verification',
        'delayed',
        'rework',
        'sedang_mengerjakan',
        'to_do', // untuk data lama
    ];

    protected $table = 'jobdesk_tasks';

    protected $fillable = [
        'jobdesk_id',
        'judul',
        'pic',
        'schedule_date',
        'start_time',
        'end_time',
        'status',
        'progress',
        'result',
        'shortcoming',
        'detail',
        'lat',
        'lng',
        'address',
        'proof_link',
        'is_template',
        'parent_task_id',

        // ✅ timestamp per-task (dibuat/diupdate saat dikerjakan)
        'submitted_at',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'progress'      => 'integer',
        'lat'           => 'float',
        'lng'           => 'float',
        'is_template'   => 'boolean',

        // ✅ Carbon datetime
        'submitted_at'  => 'datetime',
    ];

    // ✅ Tambah submitted_at_wib supaya gampang dipakai di blade
    protected $appends = [
        'status_label',
        'photo_urls',
        'submitted_at_wib',
    ];

    public function jobdesk(): BelongsTo
    {
        return $this->belongsTo(Jobdesk::class, 'jobdesk_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(JobdeskTaskPhoto::class, 'task_id')->orderByDesc('id');
    }

    public function getStatusLabelAttribute(): string
    {
        $status = strtolower((string) ($this->status ?? ''));

        return match ($status) {
            'done'               => 'Done',
            'in_progress'        => 'In Progress',
            'pending'            => 'Pending',
            'cancelled'          => 'Cancelled',
            'verification'       => 'Verification',
            'delayed'            => 'Delayed',
            'rework'             => 'Rework',
            'sedang_mengerjakan' => 'Sedang Mengerjakan',
            'to_do'              => 'To Do',
            default              => ucfirst($status ?: 'Unknown'),
        };
    }

    public function getPhotoUrlsAttribute(): array
    {
        $list = $this->relationLoaded('photos') ? $this->photos : $this->photos()->get();
        return $list->map(fn ($p) => $p->url)->values()->all();
    }

    /**
     * ✅ Format WIB untuk timestamp per-task.
     * Prioritas:
     * 1) submitted_at (timestamp ketika task dikerjakan/diupdate)
     * 2) created_at (fallback)
     */
    public function getSubmittedAtWibAttribute(): ?string
    {
        $tz = 'Asia/Jakarta';

        if (!empty($this->submitted_at)) {
            return $this->submitted_at->copy()->timezone($tz)->format('d/m/Y H:i') . ' WIB';
        }

        if (!empty($this->created_at)) {
            return $this->created_at->copy()->timezone($tz)->format('d/m/Y H:i') . ' WIB';
        }

        return null;
    }
}
