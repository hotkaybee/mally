<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            'source' => $user->telegram_id ? 'telegram' : 'website',
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
     * The owner chooses a password, so later they can sign in either through
     * the bot again or with phone + password.
     *
     * @param  array{telegram_user_id: string, telegram_username: string, phone: string}  $identity
     * @return array{result: string, user?: User}
     */
    public function registerFromTelegram(array $identity, string $name, string $password): array
    {
        return $this->withPhoneLock($identity['phone'], function () use ($identity, $name, $password) {
            if ($this->findByTelegram($identity['telegram_user_id'])) {
                return ['result' => self::TELEGRAM_TAKEN];
            }

            if ($this->phoneTaken($identity['phone'])) {
                return ['result' => self::PHONE_TAKEN];
            }

            $user = User::create([
                'name' => $name,
                // users.email is required and unique; Telegram accounts have none.
                'email' => 'tg_'.$identity['telegram_user_id'].'@telegram.mally.local',
                'phone' => $identity['phone'],
                'telegram_id' => $identity['telegram_user_id'],
                'phone_verified_at' => now(),
                'password' => Hash::make($password),
            ]);

            $customer = $this->customerFor($user);
            $this->rememberTelegramHandle($customer, $identity['telegram_username']);

            return ['result' => self::OK, 'user' => $user];
        });
    }

    /**
     * Set a new password once the owner has proved who they are (email code
     * or Telegram). The new hash signs out the account's other sessions.
     */
    public function changePassword(User $user, string $password): void
    {
        $user->update(['password' => Hash::make($password)]);
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
