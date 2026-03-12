<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SsoSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'application_id',
        'session_token',
        'access_token',
        'refresh_token',
        'expires_at',
        'ip_address',
        'user_agent',
        'last_activity',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_activity' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user that owns the session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the application associated with the session.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Check if the session is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the session is active.
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }
}
