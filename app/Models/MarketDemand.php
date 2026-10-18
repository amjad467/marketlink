<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketDemand extends Model
{
    protected $fillable = [
        'retailer_company_id',
        'category_id',
        'title',
        'quantity',
        'unit',
        'target_unit_price',
        'status',
        'notes',
    ];

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'retailer_company_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
