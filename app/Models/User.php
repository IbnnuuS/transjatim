<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'username',
        'role',
        'division',
        'divisi',
        'avatar',
        'avatar_url',   // tetap ada biar gak rusak modul lain
        'providers',
        'password',
        'full_name',
        'birth_date',
        'koridor',
        'last_login_at',
        'last_logout_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'last_login_at'     => 'datetime',
            'last_logout_at'    => 'datetime',
            'providers'         => 'array',
            'birth_date'        => 'date',
        ];
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? '') === 'admin';
    }

    /**
     * ✅ Avatar URL helper (dipakai semua blade admin/user)
     */
    public function getAvatarDisplayAttribute(): string
    {
        $raw = $this->avatar ?? $this->avatar_url ?? null;

        // Debug Log
        \Illuminate\Support\Facades\Log::info("Avatar Debug: Raw=[{$raw}]");

        if (!$raw) return '/assets/img/profile-img.jpg';

        $path = str_replace(['storage/', '/storage/', 'public/'], '', $raw);

        \Illuminate\Support\Facades\Log::info("Avatar Debug: Path=[{$path}], Exists=[" . (Storage::disk('public')->exists($path) ? 'YES' : 'NO') . "]");

        return Storage::disk('public')->exists($path)
            ? '/storage/' . $path
            : '/assets/img/profile-img.jpg';
    }

    public function getLastLoginAtLocalAttribute()
    {
        $tz = config('app.timezone', 'UTC');
        return $this->last_login_at ? $this->last_login_at->timezone($tz) : null;
    }

    public function getLastLogoutAtLocalAttribute()
    {
        $tz = config('app.timezone', 'UTC');
        return $this->last_logout_at ? $this->last_logout_at->timezone($tz) : null;
    }

    public function getLastLoginWibAttribute()
    {
        return $this->last_login_at ? $this->last_login_at->timezone('Asia/Jakarta') : null;
    }

    public function getLastLogoutWibAttribute()
    {
        return $this->last_logout_at ? $this->last_logout_at->timezone('Asia/Jakarta') : null;
    }
}
