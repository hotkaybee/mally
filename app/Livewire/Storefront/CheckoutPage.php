<?php

namespace App\Livewire\Storefront;

use App\Exceptions\CheckoutException;
use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Customer;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\StorefrontService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CheckoutPage extends Component
{
    use InteractsWithStore;

    /** Unpaid orders hold stock, so one account can't reserve the whole shop. */
    public const MAX_OPEN_UNPAID_ORDERS = 3;

    public const MAX_ORDERS_PER_HOUR = 5;

    /** Generous: mobile carriers in UZ put many people behind one IP. */
    public const MAX_ORDERS_PER_HOUR_PER_IP = 30;

    public const MAX_PROMO_ATTEMPTS = 10;

    public string $firstName = '';

    public string $lastName = '';

    public string $city = '';

    public string $address = '';

    public string $phoneBilling = '+998 ';

    public string $comment = '';

    public string $promoCode = '';

    #[Locked]
    public ?string $appliedPromo = null;

    #[Locked]
    public float $discountUsd = 0.0;

    /** One token per opened checkout page, so a double submit places one order. */
    #[Locked]
    public string $checkoutToken = '';

    public function mount(): void
    {
        $service = app(StorefrontService::class);
        $cartDetails = $service->getCartItemsWithProducts();

        if (empty($cartDetails['items'])) {
            $this->redirect(route('cart'));

            return;
        }

        $this->checkoutToken = (string) Str::uuid();
        $this->comment = (string) session('mally_order_note', '');

        $activePromo = $service->getPromoCode();
        $this->promoCode = $activePromo ?? '';
        $this->appliedPromo = $activePromo;
        $this->discountUsd = (float) ($cartDetails['discount_usd'] ?? 0.0);

        $customer = $service->getCurrentCustomer();
        if ($customer) {
            $this->fillFromCustomer($customer);
        }
    }

    protected function onCustomerAuthenticated(Customer $customer): void
    {
        $this->fillFromCustomer($customer);
    }

    protected function fillFromCustomer(Customer $customer): void
    {
        $this->firstName = (string) ($customer->first_name ?? '');
        $this->lastName = (string) ($customer->last_name ?? '');
        $phone = trim((string) ($customer->phone_number ?? ''));
        $this->phoneBilling = $phone !== ''
            ? (app(StorefrontService::class)->normalizeAndValidateUzPhone($phone) ?? $phone)
            : '+998 ';
        $this->city = (string) ($customer->city ?? '');
        $this->address = (string) ($customer->address ?? '');
    }

    public function applyPromo(): void
    {
        if (trim($this->promoCode) === '') {
            $this->dispatch('toast', message: __('Enter a promo code'), type: 'error');

            return;
        }

        // Guessing promo codes one request at a time.
        $promoKey = 'checkout-promo:'.request()->ip();
        if (RateLimiter::tooManyAttempts($promoKey, self::MAX_PROMO_ATTEMPTS)) {
            $this->dispatch('toast', message: __('Too many attempts. Please try again later.'), type: 'error');

            return;
        }

        $service = app(StorefrontService::class);
        $cartDetails = $service->getCartItemsWithProducts();
        $res = $service->applyPromoCode($this->promoCode, $cartDetails['subtotal_usd']);

        if (! ($res['success'] ?? false)) {
            RateLimiter::hit($promoKey, 600);
        }

        if ($res['success'] ?? false) {
            $this->appliedPromo = $res['code'];
            $this->discountUsd = (float) $res['discountUsd'];
        } else {
            $this->appliedPromo = null;
            $this->discountUsd = 0.0;
            $this->dispatch('toast', message: $res['error'] ?? __('Invalid promo code'), type: 'error');
        }
    }

    public function placeOrder(): void
    {
        $service = app(StorefrontService::class);
        $customer = $service->getCurrentCustomer();

        if (! $customer) {
            $this->openAuthModal(__('Please sign in or register to place an order'));

            return;
        }

        // Re-check prices and stock right before placing the order.
        $validation = $service->validateAndSyncCart();
        $cartDetails = $validation['cart'];
        $this->appliedPromo = $cartDetails['promo_code'];
        $this->discountUsd = (float) $cartDetails['discount_usd'];

        if (! $validation['valid']) {
            $this->dispatch('toast', message: implode(' ', $validation['messages']).' '.__('Please review your order and confirm it again.'), type: 'error');

            if (empty($cartDetails['items'])) {
                $this->redirect(route('cart'));
            }

            return;
        }

        if (empty($cartDetails['items'])) {
            $this->redirect(route('cart'));

            return;
        }

        $firstName = trim($this->firstName);
        $lastName = trim($this->lastName);
        $address = trim($this->address);
        $comment = trim($this->comment);

        if ($firstName === '' || $address === '') {
            $this->dispatch('toast', message: __('Please enter your name and delivery address'), type: 'error');

            return;
        }

        if (mb_strlen($firstName) > 100 || mb_strlen($lastName) > 100 || mb_strlen($address) > 500 || mb_strlen($comment) > 1000) {
            $this->dispatch('toast', message: __('Message is too long.'), type: 'error');

            return;
        }

        // The phone always comes from the signed-in customer, never from the form.
        $phone = $service->normalizeAndValidateUzPhone((string) $customer->phone_number) ?? trim((string) $customer->phone_number);
        if ($phone === '') {
            $this->dispatch('toast', message: __('Please enter a valid phone number (+998 followed by 9 digits)'), type: 'error');

            return;
        }

        // One checkout at a time per customer: parallel submits must not pass
        // the unpaid-order cap and the rate limit together.
        $lock = Cache::lock('checkout-customer:'.$customer->id, 30);
        if (! $lock->get()) {
            $this->dispatch('toast', message: __('Too many attempts. Please try again later.'), type: 'error');

            return;
        }

        try {
            $openUnpaid = Order::where('customer_id', $customer->id)
                ->where('status', 'processing')
                ->where('amount_paid', '<=', 0)
                ->count();
            if ($openUnpaid >= self::MAX_OPEN_UNPAID_ORDERS) {
                $this->dispatch('toast', message: __('You already have unpaid orders. Please pay for them or contact us before placing a new one.'), type: 'error');

                return;
            }

            $rateKeys = [
                'checkout-orders:customer:'.$customer->id => self::MAX_ORDERS_PER_HOUR,
                'checkout-orders:ip:'.request()->ip() => self::MAX_ORDERS_PER_HOUR_PER_IP,
            ];
            foreach ($rateKeys as $rateKey => $limit) {
                if (RateLimiter::tooManyAttempts($rateKey, $limit)) {
                    $this->dispatch('toast', message: __('Too many attempts. Please try again later.'), type: 'error');

                    return;
                }
            }

            if ($this->checkoutToken === '' || ! Cache::add('checkout:'.$this->checkoutToken, true, now()->addDay())) {
                return; // this checkout page was already submitted
            }

            $city = trim($this->city) ?: ($customer->city ?: 'Ташкент');
            $quantities = [];
            foreach ($cartDetails['items'] as $item) {
                $id = (int) $item['product']['id'];
                $quantities[$id] = ($quantities[$id] ?? 0) + (int) $item['quantity'];
            }

            try {
                $order = app(OrderService::class)->placeOrder($customer, $quantities, [
                    'customer_name' => trim($firstName.' '.$lastName),
                    'customer_phone' => $phone,
                    'city' => $city,
                    'address' => $address,
                    'comment' => $comment !== '' ? $comment : null,
                    'promo_code' => $this->appliedPromo,
                ]);
            } catch (CheckoutException $e) {
                Cache::forget('checkout:'.$this->checkoutToken);
                $this->dispatch('toast', message: $e->getMessage(), type: 'error');

                return;
            } catch (\Throwable $e) {
                Cache::forget('checkout:'.$this->checkoutToken);
                report($e);
                $this->dispatch('toast', message: __('We could not place your order. Please try again or contact us.'), type: 'error');

                return;
            }

            foreach (array_keys($rateKeys) as $rateKey) {
                RateLimiter::hit($rateKey, 3600);
            }
        } finally {
            $lock->release();
        }

        $customer->update([
            'first_name' => $firstName,
            'last_name' => $lastName !== '' ? $lastName : $customer->last_name,
            'city' => $city,
            'address' => $address,
        ]);

        $service->clearCart();
        session()->forget(['mally_promo_code', 'mally_order_note']);

        $this->redirect(route('checkout.receipt', [
            'orderNumber' => $order->order_number,
            'orderHash' => $order->secure_hash,
        ]), navigate: true);
    }

    public function render()
    {
        $service = app(StorefrontService::class);
        $cartDetails = $service->getCartItemsWithProducts();
        $this->appliedPromo = $cartDetails['promo_code'];
        $this->discountUsd = (float) $cartDetails['discount_usd'];

        return view('livewire.storefront.checkout-page', array_merge($this->sharedViewData(), [
            'cartItems' => $cartDetails['items'],
            'subtotalUsd' => $cartDetails['subtotal_usd'],
            'discountUsd' => $this->discountUsd,
            'finalTotalUsd' => $cartDetails['total_usd'],
        ]));
    }
}
