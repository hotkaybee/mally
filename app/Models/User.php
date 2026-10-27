<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * Website lookups go through this: admins share the table, but their
     * accounts don't exist for the storefront (login, reset, Telegram).
     */
    public function scopeShoppers(Builder $query): Builder
    {
        return $query->where('is_admin', false);
    }

    /** Stored in the same format as customers.phone_number. */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => Customer::normalizePhone($value));
    }

    /** Find a user by a phone number typed in any format. */
    public function scopeWherePhone(Builder $query, string $phone): Builder
    {
        $normalized = Customer::normalizePhone($phone);

        // where('phone', null) would match every user without a phone.
        return $normalized === null ? $query->whereRaw('1 = 0') : $query->where('phone', $normalized);
    }

    /** Accounts registered through Telegram have no email. */
    public function hasRealEmail(): bool
    {
        return filled($this->email);
    }
}
