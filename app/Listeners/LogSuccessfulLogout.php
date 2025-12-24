<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function __construct() {}

    public function handle(Logout $event): void
    {
        $user = $event->user;
        if ($user) {
            $user->forceFill([
                'last_logout_at' => now(),
            ])->save();
        }
    }
}
