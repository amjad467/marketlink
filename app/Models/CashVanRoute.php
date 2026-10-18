<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashVanRoute extends Model
{
    protected $fillable = [
        'supplier_company_id',
        'cash_van_id',
        'driver_user_id',
        'route_name',
        'scheduled_date',
        'status',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function van(): BelongsTo
    {
        return $this->belongsTo(CashVan::class, 'cash_van_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(CashVanRouteStop::class, 'route_id')->orderBy('stop_order');
    }
}
