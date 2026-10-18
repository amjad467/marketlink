<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'customer_ledger_entries';

    protected $fillable = [
        'supplier_company_id',
        'retailer_company_id',
        'entry_type',
        'reference_id',
        'reference_number',
        'entry_date',
        'description',
        'debit_amount',
        'credit_amount',
        'running_balance',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'entry_date' => 'datetime',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'retailer_company_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
