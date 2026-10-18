<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'quotation_proposal_id',
        'retailer_company_id',
        'supplier_company_id',
        'status',
        'currency',
        'subtotal_amount',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'payment_terms',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'estimated_delivery_date',
        'actual_delivery_date',
        'supplier_notes',
        'retailer_notes',
        'waybill_number',
        'dispatched_at',
        'driver_name',
        'driver_phone',
        'vehicle_plate',
        'received_at',
        'inspected_by',
        'inspection_notes',
        'paid_amount',
        'remaining_debt',
        'payment_status',
        'payment_method',
        'delivery_otp',
        'delivery_signature',
        'damage_notes',
        'damage_amount',
        'credit_due_date',
        'stock_reserved',
        'stock_deducted',
        'driver_id',
    ];

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->latest();
    }

    protected $casts = [
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_debt' => 'decimal:2',
        'stock_reserved' => 'boolean',
        'stock_deducted' => 'boolean',
        'delivery_latitude' => 'float',
        'delivery_longitude' => 'float',
    ];

    /**
     * Get direct Google Maps Navigation / Turn-by-Turn URL for driver
     */
    public function getNavigationUrlAttribute(): string
    {
        if ($this->delivery_latitude && $this->delivery_longitude) {
            return "https://www.google.com/maps/dir/?api=1&destination={$this->delivery_latitude},{$this->delivery_longitude}&travelmode=driving";
        }

        if ($this->retailer?->latitude && $this->retailer?->longitude) {
            return "https://www.google.com/maps/dir/?api=1&destination={$this->retailer->latitude},{$this->retailer->longitude}&travelmode=driving";
        }

        if (!empty($this->retailer?->google_maps_url)) {
            return $this->retailer->google_maps_url;
        }

        $dest = $this->delivery_address ?: (($this->retailer?->address_line ?? '') . ' ' . ($this->retailer?->city ?? ''));
        $dest = trim($dest);
        if (empty($dest)) {
            $dest = 'Kurdistan';
        }

        return "https://www.google.com/maps/dir/?api=1&destination=" . urlencode($dest) . "&travelmode=driving";
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'retailer_company_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(SupplierInvoice::class);
    }

    public function deliveryProof(): HasOne
    {
        return $this->hasOne(DeliveryProof::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(OrderInstallment::class)->orderBy('installment_number');
    }
}
