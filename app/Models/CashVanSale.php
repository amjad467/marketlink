<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashVanSale extends Model
{
    protected $fillable = [
        'sale_number',
        'supplier_company_id',
        'cash_van_id',
        'retailer_company_id',
        'driver_user_id',
        'route_stop_id',
        'sale_type',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'payment_method',
        'paid_amount',
        'balance_due',
        'status',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function van(): BelongsTo
    {
        return $this->belongsTo(CashVan::class, 'cash_van_id');
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'retailer_company_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function routeStop(): BelongsTo
    {
        return $this->belongsTo(CashVanRouteStop::class, 'route_stop_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CashVanSaleItem::class, 'sale_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(CashVanReturn::class, 'sale_id');
    }
}
