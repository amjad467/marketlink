<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashVanReconciliation extends Model
{
    protected $fillable = [
        'reconciliation_number',
        'cash_van_id',
        'driver_user_id',
        'supplier_company_id',
        'shift_date',
        'opening_cash',
        'total_cash_sales',
        'total_credit_sales',
        'total_expenses',
        'expected_cash',
        'actual_cash_handed',
        'cash_discrepancy',
        'status',
        'verified_by_user_id',
        'notes',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'opening_cash' => 'decimal:2',
        'total_cash_sales' => 'decimal:2',
        'total_credit_sales' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash_handed' => 'decimal:2',
        'cash_discrepancy' => 'decimal:2',
    ];

    public function van(): BelongsTo
    {
        return $this->belongsTo(CashVan::class, 'cash_van_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
