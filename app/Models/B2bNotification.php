<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2bNotification extends Model
{
    protected $table = 'b2b_notifications';

    protected $fillable = [
        'user_id',
        'company_id',
        'type',
        'title',
        'message',
        'action_url',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public static function send(
        ?int $companyId,
        string $title,
        string $message,
        string $type = 'general',
        ?string $actionUrl = null,
        ?int $userId = null,
        array $data = []
    ): self {
        return self::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
