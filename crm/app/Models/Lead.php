<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_QUEUED_FOR_AUDIT = 'queued_for_audit';
    public const STATUS_AUDITING = 'auditing';
    public const STATUS_AUDIT_FAILED = 'audit_failed';
    public const STATUS_AUDITED = 'audited';
    public const STATUS_NEEDS_REVIEW = 'needs_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPORTED = 'exported';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'business_name',
        'category',
        'city',
        'country',
        'website_url',
        'source',
        'source_url',
        'phone',
        'email',
        'status',
        'notes',
    ];

    public function auditJobs(): HasMany
    {
        return $this->hasMany(AuditJob::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }
}

