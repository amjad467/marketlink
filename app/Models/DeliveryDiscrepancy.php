<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryDiscrepancy extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'ordered_quantity' => 'integer',
        'delivered_quantity' => 'integer',
    ];

    public function deliveryProof()
    {
        return $this->belongsTo(DeliveryProof::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
}
