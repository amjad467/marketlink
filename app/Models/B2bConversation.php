<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class B2bConversation extends Model
{
    protected $fillable = [
        'supplier_company_id',
        'retailer_company_id',
        'subject',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'retailer_company_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(B2bMessage::class, 'conversation_id')->orderBy('created_at', 'asc');
    }
}
