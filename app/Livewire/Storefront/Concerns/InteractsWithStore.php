<?php

namespace App\Livewire\Storefront\Concerns;

use App\Models\Customer;
use App\Services\PhoneVerificationService;
use App\Services\StorefrontService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

trait InteractsWithStore
{
    public bool $isAuthModalOpen = false;

    #[Locked]
    public string $authStep = 'phone';

    public string $authPhone = '+998 ';

    public string $authCode = '';

    public string $authFirstName = '';

    public string $authLastName = '';

    public string $authCity = 'Ташкент';

    public string $authAddress = '';

    public string $authModalMessage = '';

    protected function store(): StorefrontService
    {
        return app(StorefrontService::class);
    }

    public function t(string $key, ?string $fallback = null): string
    {
        return $this->store()->t($key, $fallback);
    }

    public function formatPrice(float|int|string|null $priceVal, ?string $forceCurrency = null): string
    {
        return $this->store()->formatPrice($priceVal, $forceCurrency);
    }

    public function switchLanguage(string $lang): void
    {
        $this->store()->setLocale($lang);
    }

    public function switchCurrency(string $currency): void
    {
        $this->store()->setCurrency($currency);
    }

    public function addToCart(string $productKey, bool $redirectToCart = false): void
    {
        $res = $this->store()->addToCart($productKey);
        if (! ($res['success'] ?? false) && ! empty($res['message'])) {
            $this->dispatch('toast', message: $res['message'], type: 'error');

            return;
        }

        if ($redirectToCart) {
            $this->redirect('/cart', navigate: true);
        }
    }

    public function updateCartQuantity(string $productKey, int $qty): void
    {
        $res = $this->store()->updateCartQuantity($productKey, $qty);
        if (! ($res['success'] ?? true) && ! empty($res['message'])) {
            $this->dispatch('toast', message: $res['message'], type: 'error');
        }
    }

    public function updateCartQty(string|int $productKey, int $qty): void
    {
        $this->updateCartQuantity((string) $productKey, $qty);
    }

    public function removeFromCart(string|int $productKey): void
    {
        $this->store()->removeFromCart((string) $productKey);
    }

    public function clearCart(): void
    {
        $this->store()->clearCart();
    }

    public function toggleFavorite(string $productKey): void
    {
        $this->store()->toggleFavorite($productKey);
    }

    public function openAuthModal(string $message = ''): void
    {
        $this->authModalMessage = $message;
        $this->authStep = 'phone';
        $this->authPhone = '+998 ';
        $this->authCode = '';
        $this->authFirstName = '';
        $this->authLastName = '';
        $this->authCity = 'Ташкент';
        $this->authAddress = '';
        $this->isAuthModalOpen = true;
    }

    public function closeAuthModal(): void
    {
        $this->isAuthModalOpen = false;
    }

    /**
     * Only the steps that don't depend on a verified phone can be opened
     * from the browser; 'code' and 'complete-profile' are reached through
     * sendAuthCode() / verifyAuthCode() only.
     */
    public function setAuthStep(string $step): void
    {
        if (in_array($step, ['phone', 'telegram'], true)) {
            $this->authStep = $step;
        }
    }

    public function sendAuthCode(): void
    {
        $phone = $this->authStep === 'code'
            ? $this->phoneVerification()->pendingPhone()
            : $this->store()->normalizeAndValidateUzPhone($this->authPhone);

        if (! $phone) {
            $this->dispatch('toast', message: __('Please enter a valid phone number (+998 followed by 9 digits)'), type: 'error');

            return;
        }

        $result = $this->phoneVerification()->send($phone, (string) request()->ip());

        if ($result === PhoneVerificationService::THROTTLED) {
            $this->dispatch('toast', message: __('Too many code requests. Please wait and try again.'), type: 'error');

            return;
        }

        if ($result === PhoneVerificationService::UNAVAILABLE) {
            $this->dispatch('toast', message: __('We could not send the code right now. Please contact us on Telegram.'), type: 'error');

            return;
        }

        $this->authPhone = $phone;
        $this->authCode = '';
        $this->authStep = 'code';
    }

    public function verifyAuthCode(): void
    {
        if (trim($this->authCode) === '') {
            $this->dispatch('toast', message: __('Please enter the verification code'), type: 'error');

            return;
        }

        $verification = $this->phoneVerification();
        $phone = $verification->pendingPhone();
        $result = $verification->verify($this->authCode);
        $this->authCode = '';

        if ($result === PhoneVerificationService::INVALID) {
            $this->dispatch('toast', message: __('Invalid verification code'), type: 'error');

            return;
        }

        if ($result !== PhoneVerificationService::VERIFIED) {
            $this->authStep = 'phone';
            $this->dispatch('toast', message: __('The code has expired. Please request a new one.'), type: 'error');

            return;
        }

        $existing = Customer::wherePhone($phone)->first();
        if ($existing) {
            $verification->forgetVerified();
            $this->finishLogin($existing);

            return;
        }

        $this->authPhone = $phone;
        $this->authStep = 'complete-profile';
    }

    public function completeAuthRegister(): void
    {
        $verification = $this->phoneVerification();
        $phone = $verification->verifiedPhone();

        if (! $phone) {
            $this->authStep = 'phone';
            $this->dispatch('toast', message: __('Please confirm your phone number first.'), type: 'error');

            return;
        }

        $firstName = trim($this->authFirstName);
        $lastName = trim($this->authLastName);
        $city = trim($this->authCity) ?: 'Ташкент';
        $address = trim($this->authAddress);

        if ($firstName === '' || mb_strlen($firstName) > 100 || mb_strlen($lastName) > 100 || mb_strlen($address) > 500) {
            $this->dispatch('toast', message: __('Please enter your first name'), type: 'error');

            return;
        }

        // Serialize registrations for the same phone so two parallel requests
        // can't both pass the "does it exist" check.
        $customer = Cache::lock('register-phone:'.preg_replace('/\D/', '', $phone), 10)->block(5, function () use ($phone, $firstName, $lastName, $city, $address) {
            if (Customer::wherePhone($phone)->exists()) {
                return null;
            }

            return Customer::create([
                'phone_number' => $phone,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'city' => $city,
                'address' => $address !== '' ? $address : null,
                'email' => 'client_'.Str::lower(Str::random(12)).'@mally.uz',
                'source' => 'instagram',
            ]);
        });

        $verification->forgetVerified();

        if (! $customer) {
            $this->authStep = 'phone';
            $this->dispatch('toast', message: __('An account with this phone number already exists. Please sign in.'), type: 'error');

            return;
        }

        $this->finishLogin($customer);
    }

    public function logoutCustomer(): void
    {
        $this->store()->logoutCustomer();
        $this->redirect(route('home'));
    }

    protected function finishLogin(Customer $customer): void
    {
        $this->store()->loginCustomer($customer);
        $this->isAuthModalOpen = false;
        $this->authStep = 'phone';

        if (method_exists($this, 'onCustomerAuthenticated')) {
            $this->onCustomerAuthenticated($customer);
        }
    }

    protected function phoneVerification(): PhoneVerificationService
    {
        return app(PhoneVerificationService::class);
    }

    protected function sharedViewData(): array
    {
        $store = $this->store();
        $rateData = $store->getExchangeRateData();

        return [
            'store' => $store,
            'locale' => $store->getLocale(),
            'currency' => $store->getCurrency(),
            'exchangeRate' => $rateData['rate'],
            'rateSource' => $rateData['source'],
            'cartCount' => $store->getCartCount(),
            'favoritesCount' => $store->getFavoritesCount(),
            'currentCustomer' => $store->getCurrentCustomer(),
            'headerCategories' => $store->getHeaderCategories(),
        ];
    }
}
