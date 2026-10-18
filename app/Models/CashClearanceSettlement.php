<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashClearanceSettlement extends Model
{
    use HasFactory;

    protected $table = 'cash_clearance_settlements';

    protected $fillable = [
        'settlement_number',
        'supplier_company_id',
        'driver_user_id',
        'verified_by_user_id',
        'settlement_date',
        'total_cash_collected',
        'total_iqd_collected',
        'actual_cash_deposited',
        'discrepancy_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'total_cash_collected' => 'decimal:2',
        'total_iqd_collected' => 'decimal:2',
        'actual_cash_deposited' => 'decimal:2',
        'discrepancy_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
