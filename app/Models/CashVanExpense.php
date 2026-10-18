<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashVanExpense extends Model
{
    protected $fillable = [
        'cash_van_id',
        'driver_user_id',
        'supplier_company_id',
        'expense_type',
        'amount',
        'notes',
        'receipt_photo_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
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
}
