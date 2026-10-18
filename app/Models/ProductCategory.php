<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCategory extends Model
{
    protected $fillable = [
        'parent_id',
        'name_en',
        'name_ar',
        'name_ku',
        'slug',
        'icon_url',
        'is_active',
        'display_order',
    ];

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'ku') {
            return $this->name_ku ?: ($this->name_ar ?: $this->name_en);
        } elseif ($locale === 'ar') {
            return $this->name_ar ?: ($this->name_ku ?: $this->name_en);
        }
        return $this->name_en;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
