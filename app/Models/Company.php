<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company extends Model
{
    protected $fillable = [
        'name',
        'trade_license_number',
        'tax_number',
        'business_type',
        'verification_status',
        'account_status',
        'primary_email',
        'primary_phone',
        'website',
        'country',
        'province',
        'city',
        'business_category',
        'address_line',
        'latitude',
        'longitude',
        'google_maps_url',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'suspension_reason',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Get direct Google Maps Navigation / Directions URL
     */
    public function getNavigationUrlAttribute(): string
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps/dir/?api=1&destination={$this->latitude},{$this->longitude}&travelmode=driving";
        }

        if (!empty($this->google_maps_url)) {
            return $this->google_maps_url;
        }

        $query = trim(($this->address_line ? ($this->address_line . ', ') : '') . ($this->city ?? ''));
        if (empty($query)) {
            $query = 'Kurdistan';
        }

        return "https://www.google.com/maps/dir/?api=1&destination=" . urlencode($query) . "&travelmode=driving";
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(BusinessVerification::class);
    }

    public function supplierProfile(): HasOne
    {
        return $this->hasOne(SupplierProfile::class, 'company_id');
    }

    public function retailerProfile(): HasOne
    {
        return $this->hasOne(RetailerProfile::class, 'company_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'supplier_company_id');
    }

    public function purchaseOrdersAsRetailer(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'retailer_company_id');
    }

    public function purchaseOrdersAsSupplier(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_company_id');
    }

    public function isSupplier(): bool
    {
        return $this->business_type === 'supplier';
    }

    public function isRetailer(): bool
    {
        return $this->business_type === 'retailer';
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }
}
