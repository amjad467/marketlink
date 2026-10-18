<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashVan extends Model
{
    protected $fillable = [
        'company_id',
        'driver_user_id',
        'vehicle_name',
        'plate_number',
        'model',
        'operating_mode',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(CashVanInventory::class, 'cash_van_id');
    }

    public function stockTransfers(): HasMany
    {
        return $this->hasMany(CashVanStockTransfer::class, 'cash_van_id');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(CashVanRoute::class, 'cash_van_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(CashVanSale::class, 'cash_van_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CashVanExpense::class, 'cash_van_id');
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(CashVanReconciliation::class, 'cash_van_id');
    }
}
