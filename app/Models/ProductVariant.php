<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku', 'color', 'color_hex', 'size', 'pack_size', 'pack_label',
        'price', 'stock', 'image', 'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'stock' => 'integer',
        'pack_size' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['label', 'in_stock'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getLabelAttribute(): string
    {
        $parts = array_filter([
            $this->color,
            $this->size,
            $this->pack_label ?: ($this->pack_size > 1 ? $this->pack_size.' ширхэг' : null),
        ]);

        return implode(' / ', $parts) ?: 'Стандарт';
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock > 0;
    }
}
