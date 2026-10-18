<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashVanRouteStop extends Model
{
    protected $fillable = [
        'route_id',
        'retailer_company_id',
        'stop_order',
        'status',
        'arrival_time',
        'departure_time',
        'notes',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'departure_time' => 'datetime',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(CashVanRoute::class, 'route_id');
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'retailer_company_id');
    }
}
