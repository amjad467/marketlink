<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceiptVoucher extends Model
{
    use HasFactory;

    protected $table = 'payment_receipt_vouchers';

    protected $fillable = [
        'voucher_number',
        'supplier_company_id',
        'retailer_company_id',
        'collected_by_user_id',
        'purchase_order_id',
        'cash_van_sale_id',
        'amount',
        'currency',
        'amount_iqd',
        'exchange_rate',
        'payment_method',
        'custody_status',
        'verified_by_user_id',
        'verified_at',
        'cheque_number',
        'cheque_due_date',
        'transaction_reference',
        'receipt_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_iqd' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'receipt_date' => 'date',
        'cheque_due_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function getIsVerifiedAttribute(): bool
    {
        return $this->custody_status === 'deposited_to_safe' || !is_null($this->verified_at);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'retailer_company_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by_user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function cashVanSale(): BelongsTo
    {
        return $this->belongsTo(CashVanSale::class, 'cash_van_sale_id');
    }
}
