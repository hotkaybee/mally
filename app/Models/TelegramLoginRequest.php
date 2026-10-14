<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramLoginRequest extends Model
{
    public const PURPOSE_LOGIN = 'login';

    public const PURPOSE_LINK = 'link';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONTACT_REQUESTED = 'contact_requested';

    public const STATUS_CODE_SENT = 'code_sent';

    public const STATUS_USED = 'used';

    protected $fillable = [
        'token_hash',
        'session_hash',
        'purpose',
        'user_id',
        'status',
        'chat_id',
        'telegram_user_id',
        'telegram_username',
        'first_name',
        'last_name',
        'phone',
        'code_hash',
        'attempts',
        'expires_at',
        'used_at',
    ];

    protected $hidden = [
        'token_hash',
        'session_hash',
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

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }
}
