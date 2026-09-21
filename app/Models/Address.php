<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = ['user_id', 'label', 'recipient_name', 'phone', 'province', 'district', 'khoroo', 'address', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    protected $appends = ['full'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAttribute(): string
    {
        $unit = $this->province === 'Улаанбаатар' ? 'дүүрэг' : 'сум';
        $khoroo = $this->khoroo ? ($this->province === 'Улаанбаатар' ? ", {$this->khoroo}-р хороо" : ", {$this->khoroo} баг") : '';

        return "{$this->province}, {$this->district} {$unit}{$khoroo}, {$this->address}";
    }
}
