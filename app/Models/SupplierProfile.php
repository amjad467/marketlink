<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProfile extends Model
{
    protected $primaryKey = 'company_id';
    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'minimum_order_total',
        'shipping_policy',
        'return_policy',
        'default_payment_terms',
        'lead_time_days',
        'currency',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
