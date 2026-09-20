<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'icon', 'image', 'description', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function getImageAttribute($value): ?string
    {
        $photo = "/images/photos/cat-{$this->slug}.jpg";
        if ($this->slug && file_exists(public_path($photo))) {
            return $photo;
        }

        return $value && ! str_contains($value, '/images/categories/') ? $value : null;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
