<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateUserLastLoginAt
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $event->user->forceFill([
            'last_login_at' => now(),
        ])->save();
    }
}
