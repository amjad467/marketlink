<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryProof extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'delivered_latitude' => 'float',
        'delivered_longitude' => 'float',
        'cash_collected' => 'float',
        'delivered_at' => 'datetime',
        'synced_offline' => 'boolean',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function discrepancies()
    {
        return $this->hasMany(DeliveryDiscrepancy::class);
    }
}
