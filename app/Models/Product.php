<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'brand', 'short_description', 'description',
        'base_price', 'compare_price', 'images', 'specs', 'is_active', 'is_featured',
        'allow_backorder', 'backorder_days', 'views', 'sold_count', 'rating', 'reviews_count',
    ];

    protected $casts = [
        'images' => 'array',
        'specs' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'allow_backorder' => 'boolean',
        'base_price' => 'float',
        'compare_price' => 'float',
        'rating' => 'float',
    ];

    protected $appends = ['total_stock', 'min_price', 'max_price', 'in_stock', 'thumbnail'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function getTotalStockAttribute(): int
    {
        if ($this->relationLoaded('variants')) {
            return (int) $this->variants->sum('stock');
        }

        return (int) $this->variants()->sum('stock');
    }

    public function getInStockAttribute(): bool
    {
        return $this->total_stock > 0;
    }

    public function getMinPriceAttribute(): float
    {
        if ($this->relationLoaded('variants') && $this->variants->count()) {
            return (float) $this->variants->min('price');
        }

        return (float) ($this->variants()->min('price') ?? $this->base_price);
    }

    public function getMaxPriceAttribute(): float
    {
        if ($this->relationLoaded('variants') && $this->variants->count()) {
            return (float) $this->variants->max('price');
        }

        return (float) ($this->variants()->max('price') ?? $this->base_price);
    }

    /**
     * Images with the generated studio photo first. Old placeholder SVGs are
     * dropped so databases seeded before the photos existed still show them.
     */
    public function getImagesAttribute($value): array
    {
        $images = is_array($value) ? $value : (json_decode($value ?? '[]', true) ?: []);
        $images = array_values(array_filter($images, fn ($i) => ! str_contains((string) $i, '/images/products/')));

        $photo = "/images/photos/{$this->slug}.jpg";
        if ($this->slug && file_exists(public_path($photo)) && ! in_array($photo, $images, true)) {
            array_unshift($images, $photo);
        }

        return $images;
    }

    public function getThumbnailAttribute(): ?string
    {
        return $this->images[0] ?? null;
    }
}
