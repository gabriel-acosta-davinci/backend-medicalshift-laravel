<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLog extends Model
{
    protected $fillable = [
        'method',
        'path',
        'ip_address',
        'user_agent',
        'user_id',
        'status_code',
        'response_time',
        'request_body',
        'response_body',
    ];

    protected function casts(): array
    {
        return [
            'response_time' => 'integer',
            'status_code' => 'integer',
        ];
    }

    /**
     * Relationship with user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
