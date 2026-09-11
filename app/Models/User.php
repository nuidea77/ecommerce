<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_COURIER = 'courier';

    protected $fillable = [
        'name', 'email', 'phone', 'role', 'address', 'city', 'is_active', 'password',
        'is_verified', 'verified_at', 'verify_provider', 'verify_subject', 'register_number',
        'last_name', 'first_name', 'verify_data',
    ];

    protected $hidden = ['password', 'remember_token', 'verify_data', 'verify_subject'];

    protected $attributes = ['role' => self::ROLE_CUSTOMER, 'is_active' => true];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'verify_data' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function needsVerification(): bool
    {
        return $this->role === self::ROLE_CUSTOMER && ! $this->is_verified;
    }

    public function isCourier(): bool
    {
        return $this->role === self::ROLE_COURIER;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Order::class, 'courier_id');
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }
}
