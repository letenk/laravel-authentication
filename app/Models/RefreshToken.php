<?php

namespace App\Models;

use App\Models\Scope\RefreshTokenScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_name',
        'device_id',
        'ip_address',
        'user_agent',
        'expires_at',
        'revoked_at',
        'replaced_by_token',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function query(): RefreshTokenScope
    {
        return parent::query();
    }

    public function newEloquentBuilder($query): RefreshTokenScope
    {
        return new RefreshTokenScope($query);
    }
}
