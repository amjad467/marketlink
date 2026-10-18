<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoice extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'supplier_company_id',
        'retailer_company_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'total_amount',
        'currency',
        'settlement_status',
        'payment_receipt_ref',
        'invoice_pdf_path',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'retailer_company_id');
    }
}
