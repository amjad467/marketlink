<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashVanReturn extends Model
{
    protected $fillable = [
        'return_number',
        'sale_id',
        'cash_van_id',
        'supplier_company_id',
        'retailer_company_id',
        'driver_user_id',
        'total_refund_amount',
        'return_reason',
        'restock_destination',
        'status',
    ];

    protected $casts = [
        'total_refund_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(CashVanSale::class, 'sale_id');
    }

    public function van(): BelongsTo
    {
        return $this->belongsTo(CashVan::class, 'cash_van_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'retailer_company_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CashVanReturnItem::class, 'return_id');
    }
}
