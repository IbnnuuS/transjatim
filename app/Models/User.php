<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Kolom yang boleh diisi mass-assignment.
     */
    protected $fillable = [
        'name',
        'email',
        'username',          // opsional
        'role',              // 'admin' | 'user'
        'division',          // opsional: 'teknik' | 'digital' | 'customer service'
        'avatar_url',        // opsional
        'providers',         // opsional: json berisi ['password','google',...]
        'password',
        'last_login_at',     // diisi saat event login
        'last_logout_at',    // diisi saat event logout (jika dipakai)
    ];

    /**
     * Disembunyikan saat serialisasi.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Cast atribut.
     * - password => hashed
     * - last_login_at/last_logout_at => datetime
     * - providers => array (jika kolom JSON ada)
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'last_login_at'     => 'datetime',
            'last_logout_at'    => 'datetime',
            'providers'         => 'array',
        ];
    }

    /**
     * Helper: apakah user adalah admin?
     */
    public function isAdmin(): bool
    {
        return ($this->role ?? '') === 'admin';
    }

    /**
     * Accessor waktu login/logout mengikuti timezone app (di .env: APP_TIMEZONE).
     * Blade contoh: {{ optional($user->last_login_at_local)?->format('d/m/Y H:i') }}
     */
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

    /**
     * Accessor khusus WIB (Asia/Jakarta).
     * Blade contoh: {{ optional($user->last_login_wib)?->format('d/m/Y H:i') }} WIB
     */
    public function getLastLoginWibAttribute()
    {
        return $this->last_login_at ? $this->last_login_at->timezone('Asia/Jakarta') : null;
    }

    public function getLastLogoutWibAttribute()
    {
        return $this->last_logout_at ? $this->last_logout_at->timezone('Asia/Jakarta') : null;
    }
}
