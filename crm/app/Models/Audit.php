<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audit extends Model
{
    protected $fillable = [
        'lead_id',
        'audit_job_id',
        'business_summary',
        'overall_score',
        'redesign_score',
        'mobile_score',
        'performance_score',
        'accessibility_score',
        'seo_score',
        'issues_json',
        'recommendations_json',
        'technology_json',
        'contact_json',
        'desktop_screenshot_path',
        'mobile_screenshot_path',
    ];

    protected function casts(): array
    {
        return [
            'issues_json' => 'array',
            'recommendations_json' => 'array',
            'technology_json' => 'array',
            'contact_json' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(AuditJob::class, 'audit_job_id');
    }
}

