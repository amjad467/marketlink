<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashVanInventory extends Model
{
    protected $fillable = [
        'cash_van_id',
        'product_id',
        'quantity',
        'reserved_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'reserved_quantity' => 'decimal:2',
    ];

    public function van(): BelongsTo
    {
        return $this->belongsTo(CashVan::class, 'cash_van_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getAvailableQuantityAttribute(): float
    {
        return (float) max(0, $this->quantity - $this->reserved_quantity);
    }
}
