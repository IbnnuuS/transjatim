<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Schema;

class Jobdesk extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $table = 'jobdesks';

    protected $fillable = [
        'user_id',
        'assignment_id',
        'divisi',
        'division',
        'submitted_at',
        'tanggal',
        'is_recurring',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'is_recurring' => 'boolean',
    ];

    // PENTING: JANGAN pakai $withCount di sini karena mengacaukan subquery ofMany/latestOfMany
    // protected $withCount = ['tasks'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Assignment::class, 'assignment_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(JobdeskTask::class, 'jobdesk_id')
            ->orderByDesc('schedule_date')
            ->orderByDesc('created_at');
    }

    public function photos(): HasManyThrough
    {
        return $this->hasManyThrough(
            JobdeskTaskPhoto::class,
            JobdeskTask::class,
            'jobdesk_id',
            'task_id'
        );
    }

    public function scopeForUser($query, int|string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInDivision($query, string $division)
    {
        if (Schema::hasColumn($this->getTable(), 'divisi')) {
            $query->where('divisi', $division);
        }
        if (Schema::hasColumn($this->getTable(), 'division')) {
            $query->orWhere('division', $division);
        }
        return $query;
    }

    public function scopeBetweenSubmitted($query, $from, $to)
    {
        if (Schema::hasColumn($this->getTable(), 'submitted_at')) {
            return $query->when($from, fn($q) => $q->where('submitted_at', '>=', $from))
                ->when($to,   fn($q) => $q->where('submitted_at', '<=', $to));
        }
        return $query->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to,   fn($q) => $q->where('created_at', '<=', $to));
    }

    public function scopeRecent($query)
    {
        if (Schema::hasColumn($this->getTable(), 'submitted_at')) {
            return $query->orderByRaw('COALESCE(submitted_at, created_at) DESC')
                ->orderByDesc('created_at');
        }
        return $query->orderByDesc('created_at');
    }

    // Getter division: prefer kolom yang ada
    public function getDivisionAttribute(): ?string
    {
        if (array_key_exists('division', $this->attributes) && !empty($this->attributes['division'])) {
            return $this->attributes['division'];
        }
        return $this->attributes['divisi'] ?? null;
    }

    // Setter division: sinkron dua kolom jika tersedia
    public function setDivisionAttribute($value): void
    {
        if (Schema::hasColumn($this->getTable(), 'division')) {
            $this->attributes['division'] = $value;
        }
        if (Schema::hasColumn($this->getTable(), 'divisi')) {
            $this->attributes['divisi'] = $value;
        }
    }

    public function getDivisionLabelAttribute(): ?string
    {
        $val = $this->division;
        return $val ? ucwords(strtolower($val)) : null;
    }

    public function getSubmittedAtWibAttribute(): ?string
    {
        return $this->submitted_at
            ? $this->submitted_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB'
            : null;
    }

    protected static function booted(): void
    {
        static::creating(function (self $jobdesk) {
            // Isi division otomatis dari user jika kosong
            if (empty($jobdesk->division) && $jobdesk->user_id) {
                $user = $jobdesk->relationLoaded('user') ? $jobdesk->user : \App\Models\User::find($jobdesk->user_id);
                if ($user) {
                    $uDiv = $user->division ?? $user->divisi ?? null;
                    if (!empty($uDiv)) {
                        $jobdesk->division = $uDiv;
                    }
                }
            }

            if (empty($jobdesk->submitted_at) && Schema::hasColumn($jobdesk->getTable(), 'submitted_at')) {
                $jobdesk->submitted_at = now();
            }
        });
    }
}
