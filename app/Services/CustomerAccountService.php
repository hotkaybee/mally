<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Storefront accounts: a User (login) owns exactly one Customer
 * (customers.user_id). Accounts are never merged and orders never move
 * between customers; an existing admin/API customer row with the same phone
 * or email is left alone.
 */
class CustomerAccountService
{
    public const OK = 'ok';

    public const TELEGRAM_TAKEN = 'telegram_taken';

    public const PHONE_TAKEN = 'phone_taken';

    public function __construct(private readonly StorefrontService $storefront) {}

    /**
     * A phone belongs to an account when a user has it, or a customer that
     * already belongs to a user has it.
     */
    public function phoneTaken(string $phone, ?int $exceptUserId = null): bool
    {
        if (PhoneNumber::normalize($phone) === null) {
            return false;
        }

        $byUser = User::wherePhone($phone)
            ->when($exceptUserId, fn ($q) => $q->whereKeyNot($exceptUserId))
            ->exists();

        $byCustomer = Customer::wherePhone($phone)
            ->whereNotNull('user_id')
            ->when($exceptUserId, fn ($q) => $q->where('user_id', '!=', $exceptUserId))
            ->exists();

        return $byUser || $byCustomer;
    }

    /** The user's own customer row, created on first use. */
    public function customerFor(User $user): Customer
    {
        $existing = Customer::where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        [$first, $last] = $this->splitName((string) $user->name);

        // customers.email is unique. If an admin/API customer already uses
        // this address we don't take it over; the shopper gets a placeholder.
        $email = mb_strtolower((string) $user->email);
        if (Customer::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            $email = 'user'.$user->id.'-'.Str::lower(Str::random(6)).'@accounts.mally.local';
        }

        return Customer::create([
            'user_id' => $user->id,
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone_number' => $user->phone,
            'source' => $user->telegram_id ? 'telegram' : 'instagram',
        ]);
    }

    /** Sign the user in on the storefront (session id is regenerated). */
    public function login(User $user): Customer
    {
        $customer = $this->customerFor($user);
        $this->storefront->loginCustomer($customer);

        return $customer;
    }

    public function findByTelegram(string $telegramUserId): ?User
    {
        return $telegramUserId === '' ? null : User::where('telegram_id', $telegramUserId)->first();
    }

    /**
     * New account from a Telegram identity verified by TelegramAuthService.
     * It has no usable password: the owner always signs in through the bot.
     *
     * @param  array{telegram_user_id: string, telegram_username: string, phone: string}  $identity
     * @return array{result: string, user?: User}
     */
    public function registerFromTelegram(array $identity, string $name): array
    {
        return $this->withPhoneLock($identity['phone'], function () use ($identity, $name) {
            if ($this->findByTelegram($identity['telegram_user_id'])) {
                return ['result' => self::TELEGRAM_TAKEN];
            }

            if ($this->phoneTaken($identity['phone'])) {
                return ['result' => self::PHONE_TAKEN];
            }

            $user = User::create([
                'name' => $name,
                'email' => 'tg_'.$identity['telegram_user_id'].'@telegram.mally.local',
                'phone' => $identity['phone'],
                'telegram_id' => $identity['telegram_user_id'],
                'phone_verified_at' => now(),
                // Random and never shown: phone + password login can't match it.
                'password' => Str::random(64),
            ]);

            $customer = $this->customerFor($user);
            $this->rememberTelegramHandle($customer, $identity['telegram_username']);

            return ['result' => self::OK, 'user' => $user];
        });
    }

    /**
     * Link a verified Telegram identity to an account whose owner is already
     * signed in (or just proved it with the password). Never touches another
     * account.
     *
     * @param  array{telegram_user_id: string, telegram_username: string, phone: string}  $identity
     */
    public function linkTelegram(User $user, array $identity): string
    {
        return $this->withPhoneLock($identity['phone'], function () use ($user, $identity) {
            $owner = $this->findByTelegram($identity['telegram_user_id']);
            if ($owner && $owner->id !== $user->id) {
                return self::TELEGRAM_TAKEN;
            }

            $user->refresh();
            if ($user->telegram_id && $user->telegram_id !== $identity['telegram_user_id']) {
                return self::TELEGRAM_TAKEN;
            }

            if ($this->phoneTaken($identity['phone'], $user->id)) {
                return self::PHONE_TAKEN;
            }

            $user->forceFill([
                'telegram_id' => $identity['telegram_user_id'],
                'phone' => $identity['phone'],
                'phone_verified_at' => now(),
            ])->save();

            $customer = $this->customerFor($user);
            $customer->update(['phone_number' => $identity['phone']]);
            $this->rememberTelegramHandle($customer, $identity['telegram_username']);

            return self::OK;
        });
    }

    private function rememberTelegramHandle(Customer $customer, string $username): void
    {
        $username = ltrim(trim($username), '@');
        if ($username !== '') {
            $customer->update(['telegram' => '@'.$username]);
        }
    }

    /**
     * Serialize account changes for one phone number so two parallel
     * requests can't both pass the "is it taken" check.
     */
    private function withPhoneLock(string $phone, \Closure $callback): mixed
    {
        $key = 'account-phone:'.preg_replace('/\D/', '', $phone);

        return Cache::lock($key, 10)->block(5, fn () => DB::transaction($callback));
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function splitName(string $name): array
    {
        $parts = explode(' ', trim(preg_replace('/\s+/', ' ', $name)), 2);

        return [($parts[0] ?? '') !== '' ? $parts[0] : 'Покупатель', $parts[1] ?? ''];
    }
}
