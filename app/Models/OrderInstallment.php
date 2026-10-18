<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderInstallment extends Model
{
    use HasFactory;

    protected $table = 'order_installments';

    protected $fillable = [
        'purchase_order_id',
        'installment_number',
        'due_date',
        'amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'paid_at',
        'payment_receipt_voucher_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function receiptVoucher(): BelongsTo
    {
        return $this->belongsTo(PaymentReceiptVoucher::class, 'payment_receipt_voucher_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast();
    }
}
