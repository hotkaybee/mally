<?php

namespace App\Livewire\Storefront\Concerns;

use App\Models\Customer;
use App\Services\StorefrontService;
use Illuminate\Support\Str;

trait InteractsWithStore
{
    public bool $isAuthModalOpen = false;

    public string $authStep = 'phone';

    public string $authPhone = '+998 ';

    public string $authCode = '';

    public ?string $authDevCode = null;

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
        $this->authDevCode = null;
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

    public function setAuthStep(string $step): void
    {
        $this->authStep = $step;
    }

    public function sendAuthCode(): void
    {
        $locale = $this->store()->getLocale();
        $normalized = $this->store()->normalizeAndValidateUzPhone($this->authPhone);

        if (! $normalized) {
            $errMsg = match ($locale) {
                'uz' => "Iltimos, to'g'ri telefon raqamini kiriting (+998 va 9 ta raqam)",
                'en' => 'Please enter a valid phone number (+998 followed by 9 digits)',
                default => 'Введите корректный номер телефона (+998 и 9 цифр)',
            };
            $this->dispatch('toast', message: $errMsg, type: 'error');

            return;
        }

        $this->authPhone = $normalized;
        $this->authDevCode = '7788';
        $this->authCode = '';
        $this->authStep = 'code';
    }

    public function fillAndVerifyDevCode(): void
    {
        $this->authCode = $this->authDevCode ?: '7788';
        $this->verifyAuthCode();
    }

    public function verifyAuthCode(): void
    {
        $locale = $this->store()->getLocale();
        $code = trim($this->authCode);
        if ($code === '') {
            $msg = match ($locale) {
                'uz' => 'Tasdiqlash kodini kiriting',
                'en' => 'Please enter the verification code',
                default => 'Введите проверочный код',
            };
            $this->dispatch('toast', message: $msg, type: 'error');

            return;
        }

        if (! in_array($code, ['7788', '1111'], true) && ($this->authDevCode && $code !== $this->authDevCode)) {
            $msg = match ($locale) {
                'uz' => "Tasdiqlash kodi noto'g'ri",
                'en' => 'Invalid verification code',
                default => 'Неверный код подтверждения',
            };
            $this->dispatch('toast', message: $msg, type: 'error');

            return;
        }

        $phone = trim($this->authPhone);
        $digits = preg_replace('/\D/', '', $phone);
        $compactPhone = '+'.$digits;

        $existing = Customer::where('phone_number', $phone)
            ->orWhere('phone_number', $compactPhone)
            ->first();

        if ($existing) {
            if ($existing->phone_number !== $phone) {
                $existing->update(['phone_number' => $phone]);
            }
            $this->store()->loginCustomer($existing);
            $this->isAuthModalOpen = false;
            if (method_exists($this, 'onCustomerAuthenticated')) {
                $this->onCustomerAuthenticated($existing);
            }

            return;
        }

        $this->authStep = 'complete-profile';
    }

    public function completeAuthRegister(): void
    {
        $locale = $this->store()->getLocale();
        $firstName = trim($this->authFirstName);
        $lastName = trim($this->authLastName);
        $city = trim($this->authCity) ?: 'Ташкент';
        $address = trim($this->authAddress);

        if ($firstName === '') {
            $msg = match ($locale) {
                'uz' => 'Iltimos, ismingizni kiriting',
                'en' => 'Please enter your first name',
                default => 'Пожалуйста, укажите имя',
            };
            $this->dispatch('toast', message: $msg, type: 'error');

            return;
        }

        $customer = Customer::create([
            'phone_number' => trim($this->authPhone),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'telegram' => null,
            'instagram' => $city.($address ? ' | '.$address : ''),
            'email' => 'client_'.Str::random(6).'@mally.uz',
            'source' => 'instagram',
        ]);

        $this->store()->loginCustomer($customer);
        $this->isAuthModalOpen = false;

        if (method_exists($this, 'onCustomerAuthenticated')) {
            $this->onCustomerAuthenticated($customer);
        }
    }

    public function logoutCustomer(): void
    {
        $this->store()->logoutCustomer();
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
