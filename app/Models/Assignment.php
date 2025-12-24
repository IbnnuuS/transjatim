<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Assignment extends Model
{
    protected $table = 'assignments';

    protected $fillable = [
        'title',
        'deadline',
        'description',
        'status',
        'progress',
        'proof_link',
        'result',
        'shortcoming',
        'detail',

        // kolom owner yang mungkin berbeda antar proyek
        'employee_id',
        'user_id',
        'karyawan_id',
        // opsional: 'division'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'progress' => 'integer',
    ];

    /** (skema lama) relasi ke user lewat employee_id */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /** semua jobdesk untuk assignment ini */
    public function jobdesks(): HasMany
    {
        return $this->hasMany(Jobdesk::class, 'assignment_id')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');
    }

    /**
     * Jobdesk terbaru untuk assignment ini.
     * Pakai ofMany('id','max') + SELECT berprefix supaya tidak ambiguous
     * saat MySQL ONLY_FULL_GROUP_BY aktif.
     * Sertakan relationship tasks untuk menghitung progress komputasi.
     */
    public function latestJobdeskWithPhotos(): HasOne
    {
        return $this->hasOne(Jobdesk::class, 'assignment_id')
            ->ofMany('id', 'max')
            ->select([
                'jobdesks.id',
                'jobdesks.assignment_id',
                'jobdesks.submitted_at',
                'jobdesks.created_at',
            ])
            ->with(['photos', 'tasks.photos', 'tasks']); // tasks diperlukan untuk hitung rata-rata progress
    }

    /**
     * Accessor: progress TERKOMPUTASI dari jobdesk terbaru (rata-rata progress task).
     * Fallback ke kolom assignments.progress jika tidak ada task.
     */
    public function getComputedProgressAttribute(): int
    {
        // gunakan relasi yang sudah di-eager load jika ada
        $latest = $this->relationLoaded('latestJobdeskWithPhotos')
            ? $this->latestJobdeskWithPhotos
            : $this->latestJobdeskWithPhotos()->with('tasks')->first();

        if ($latest && $latest->relationLoaded('tasks') && $latest->tasks->count() > 0) {
            $avg = (int) round($latest->tasks->avg('progress') ?? 0);
            return max(0, min(100, $avg));
        }

        // fallback ke nilai manual di kolom assignments.progress
        return (int) ($this->progress ?? 0);
    }

    /**
     * Accessor: status TERKOMPUTASI dari computed_progress.
     * - >=100 => done
     * - 1..99 => in_progress
     * - 0     => pending (fallback ke kolom status jika ada nilai manual)
     */
    public function getComputedStatusAttribute(): string
    {
        $p = $this->computed_progress;
        if ($p >= 100) return 'done';
        if ($p > 0)    return 'in_progress';

        return (string) ($this->status ?: 'pending');
    }

    /**
     * Accessor owner yang aman: baca kolom yang memang ada saja.
     */
    public function getOwnerUserIdAttribute(): ?int
    {
        $cols = array_filter([
            Schema::hasColumn($this->getTable(), 'user_id')      ? ($this->attributes['user_id']      ?? null) : null,
            Schema::hasColumn($this->getTable(), 'employee_id')  ? ($this->attributes['employee_id']  ?? null) : null,
            Schema::hasColumn($this->getTable(), 'karyawan_id')  ? ($this->attributes['karyawan_id']  ?? null) : null,
        ], fn($v) => !is_null($v));

        foreach ($cols as $id) return (int) $id;
        return null;
    }

    /**
     * Scope: filter assignment milik user — hanya gunakan kolom yang ADA.
     * Menghindari error "Unknown column".
     */
    public function scopeForUser($query, int $userId)
    {
        $table = $this->getTable();
        $candidates = [];

        if (Schema::hasColumn($table, 'user_id'))     $candidates[] = 'user_id';
        if (Schema::hasColumn($table, 'employee_id')) $candidates[] = 'employee_id';
        if (Schema::hasColumn($table, 'karyawan_id')) $candidates[] = 'karyawan_id';

        // Jika tidak ada kolom owner sama sekali, jangan filter (biarkan semua muncul)
        if (empty($candidates)) {
            return $query;
        }

        return $query->where(function ($q) use ($candidates, $userId) {
            foreach ($candidates as $i => $col) {
                $i === 0 ? $q->where($col, $userId) : $q->orWhere($col, $userId);
            }
        });
    }

    /** scope: urut baru berdasarkan deadline/created_at */
    public function scopeRecent($query)
    {
        return $query->orderByRaw('COALESCE(deadline, created_at) DESC')
            ->orderByDesc('created_at');
    }
}
