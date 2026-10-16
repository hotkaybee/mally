<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\PhoneNumber;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable. `is_admin` is deliberately
     * not here: admin access is granted with `php artisan admin:grant`.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'telegram_id',
        'phone_verified_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Storefront shoppers share this table, so only flagged admins may open
     * the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_admin;
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function scopeWherePhone(Builder $query, string $phone): Builder
    {
        return $query->whereIn('phone', PhoneNumber::variants($phone));
    }

    /** Placeholder email for accounts registered through Telegram without an email. */
    public function hasRealEmail(): bool
    {
        return ! str_ends_with(mb_strtolower((string) $this->email), '@telegram.mally.local');
    }
}
