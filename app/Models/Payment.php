<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'provider', 'invoice_id', 'sender_invoice_no', 'amount', 'status', 'qr_text',
        'qr_image', 'short_url', 'urls', 'raw_response', 'payment_id', 'paid_at', 'expires_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'urls' => 'array',
        'raw_response' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = ['raw_response'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
