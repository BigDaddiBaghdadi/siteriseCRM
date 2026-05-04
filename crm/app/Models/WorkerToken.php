<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerToken extends Model
{
    protected $fillable = [
        'name',
        'token_hash',
        'active',
        'last_used_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}

