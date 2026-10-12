<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthCode extends Model
{
    public const PURPOSE_REGISTER = 'register';

    public const PURPOSE_RESET = 'reset';

    protected $fillable = [
        'email',
        'purpose',
        'code_hash',
        'expires_at',
        'attempts',
        'used_at',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
