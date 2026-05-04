<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditJob extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_AUDITING = 'auditing';
    public const STATUS_AUDITED = 'audited';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'lead_id',
        'status',
        'priority',
        'attempts',
        'locked_by',
        'locked_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}

