<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SecurityAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'event_type',
        'severity',
        'endpoint',
        'method',
        'ip_address',
        'user_agent',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Helper to quickly log security anomalies, IDOR attempts, and RBAC violations
     */
    public static function logViolation(
        string $eventType,
        string $message,
        Request $request,
        ?int $companyId = null,
        ?int $userId = null,
        string $severity = 'warning',
        array $extra = []
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'company_id' => $companyId,
            'event_type' => $eventType,
            'severity' => $severity,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => array_merge([
                'message' => $message,
                'input' => $request->except(['password', 'password_confirmation', 'delivery_otp']),
            ], $extra),
        ]);
    }
}
