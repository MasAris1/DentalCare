<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginOtpChallenge extends Model
{
    protected $fillable = [
        'public_id',
        'user_id',
        'code_hash',
        'remember',
        'redirect_path',
        'attempts',
        'ip_address',
        'user_agent',
        'last_sent_at',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'remember' => 'boolean',
            'last_sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->consumed_at === null && $this->expires_at->isFuture();
    }
}
