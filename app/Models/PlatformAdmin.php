<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformAdmin extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PlatformAuditLog::class, 'admin_id');
    }
}
