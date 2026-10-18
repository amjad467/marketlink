<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2bMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_company_id',
        'message_type',
        'body',
        'attachment_url',
        'reference_id',
        'is_read',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(B2bConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'sender_company_id');
    }
}
