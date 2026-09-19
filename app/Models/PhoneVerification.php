<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One verify.mn MO-SMS session. The row is created when POST /sessions succeeds
 * and updated from GET /sessions/{id} (poll or callback).
 */
class PhoneVerification extends Model
{
    public const PENDING = 'PENDING';

    public const VERIFIED = 'VERIFIED';

    public const EXPIRED = 'EXPIRED';

    protected $fillable = [
        'user_id', 'phone', 'session_id', 'code', 'callback_token', 'sms_uri', 'display_instruction',
        'status', 'callback_status', 'expires_at', 'verified_at', 'last_checked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    protected $hidden = ['callback_token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isVerified(): bool
    {
        return $this->status === self::VERIFIED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::EXPIRED || ($this->expires_at && $this->expires_at->isPast());
    }

    public function isActive(): bool
    {
        return $this->status === self::PENDING && ! $this->isExpired();
    }
}
