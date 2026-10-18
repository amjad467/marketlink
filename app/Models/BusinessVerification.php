<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessVerification extends Model
{
    protected $fillable = [
        'company_id',
        'document_type',
        'file_path',
        'document_expiry_date',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_comments',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class, 'reviewed_by');
    }
}
