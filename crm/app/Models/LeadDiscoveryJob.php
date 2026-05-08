<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadDiscoveryJob extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const TARGET_NO_WEBSITE = 'no_website';
    public const TARGET_NEEDS_REDESIGN = 'needs_redesign';
    public const TARGET_BOTH = 'both';

    public const RESULT_LIMITS = [15, 30, 50];

    protected $fillable = [
        'niche',
        'random_niche',
        'city',
        'country',
        'result_limit',
        'target',
        'status',
        'attempts',
        'locked_by',
        'locked_at',
        'completed_at',
        'leads_found',
        'last_error',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'random_niche' => 'boolean',
            'locked_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function targetLabel(): string
    {
        return match ($this->target) {
            self::TARGET_NO_WEBSITE => 'Businesses without a website',
            self::TARGET_NEEDS_REDESIGN => 'Websites needing redesign',
            self::TARGET_BOTH => 'Both opportunity types',
            default => str_replace('_', ' ', $this->target),
        };
    }

    public function nicheLabel(): string
    {
        return $this->random_niche ? 'Random niche' : ($this->niche ?: 'Any niche');
    }
}
