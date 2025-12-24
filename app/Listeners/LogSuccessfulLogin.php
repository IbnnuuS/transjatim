<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct() {}

    public function handle(Login $event): void
    {
        $user = $event->user;
        // Simpan waktu login + IP + user agent
        $user->forceFill([
            'last_login_at'         => now(),
            'last_login_ip'         => request()->ip(),
            'last_login_user_agent' => substr((string) request()->userAgent(), 0, 1000),
        ])->save();
    }
}
