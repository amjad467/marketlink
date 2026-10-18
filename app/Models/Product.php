<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'supplier_company_id',
        'category_id',
        'sku',
        'barcode',
        'title',
        'description',
        'unit_of_measure',
        'base_wholesale_price',
        'currency',
        'minimum_order_quantity',
        'current_stock',
        'reserved_stock',
        'is_available',
        'is_active',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function volumeTiers(): HasMany
    {
        return $this->hasMany(ProductVolumeTier::class);
    }

    public function getStockQuantityAttribute(): int
    {
        return (int) ($this->attributes['current_stock'] ?? 0);
    }

    public function setStockQuantityAttribute($val): void
    {
        $this->attributes['current_stock'] = $val;
    }

    public function getWholesalePriceAttribute(): float
    {
        return (float) ($this->attributes['base_wholesale_price'] ?? 0);
    }

    public function setWholesalePriceAttribute($val): void
    {
        $this->attributes['base_wholesale_price'] = $val;
    }

    public function getCompanyIdAttribute(): ?int
    {
        return isset($this->attributes['supplier_company_id']) ? (int)$this->attributes['supplier_company_id'] : null;
    }

    public function setCompanyIdAttribute($val): void
    {
        $this->attributes['supplier_company_id'] = $val;
    }
}
