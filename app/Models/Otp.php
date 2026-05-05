<?php

namespace App\Models;

use App\Models\Scope\OtpScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Otp extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'purpose',
        'code',
        'expires_at',
        'verified_at',
        'attempt',
        'next_attempt_at',
        'submit_attempt',
    ];

    protected $casts = [
        'expires_at'      => 'datetime',
        'verified_at'     => 'datetime',
        'next_attempt_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function query(): OtpScope
    {
        return parent::query();
    }

    public function newEloquentBuilder($query): OtpScope
    {
        return new OtpScope($query);
    }
}
