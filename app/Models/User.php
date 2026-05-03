<?php

namespace App\Models;

use App\Models\Scope\UserScope;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $attributes = [
        'is_verified' => false,
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'login_type',
        'is_verified',
        'verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password'    => 'hashed',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public static function query(): UserScope
    {
        return parent::query();
    }

    public function newEloquentBuilder($query): UserScope
    {
        return new UserScope($query);
    }
}
