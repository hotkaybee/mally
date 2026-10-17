<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class AuthCode extends Model
{
    use Prunable;

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

    /** Expired for a day: deleted by the scheduled model:prune. */
    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now()->subDay());
    }
}
