<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetailerProfile extends Model
{
    protected $primaryKey = 'company_id';
    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'store_count',
        'credit_limit',
        'default_delivery_address',
        'preferred_payment_method',
        'receiving_hours',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function getOutstandingDebtAttribute(): float
    {
        return (float) \App\Models\PurchaseOrder::where('retailer_company_id', $this->company_id)
            ->where('status', '!=', 'cancelled')
            ->sum('remaining_debt')
            + (float) \App\Models\CashVanSale::where('retailer_company_id', $this->company_id)
            ->where('status', '!=', 'cancelled')
            ->sum('balance_due');
    }
}
