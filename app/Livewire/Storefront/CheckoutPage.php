<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductReceipt;
use App\Services\OrderNumberRetryService;
use App\Services\StorefrontService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CheckoutPage extends Component
{
    use InteractsWithStore;

    public string $firstName = '';

    public string $lastName = '';

    public string $city = '';

    public string $address = '';

    public string $phoneBilling = '+998 ';

    public string $email = '';

    public string $comment = '';

    public string $promoCode = '';

    public ?string $appliedPromo = null;

    public float $discountUsd = 0.0;

    public function mount(): void
    {
        $service = app(StorefrontService::class);
        $cartDetails = $service->getCartItemsWithProducts();

        if (empty($cartDetails['items'])) {
            $this->redirect(route('cart'));

            return;
        }

        $this->comment = session()->has('mally_order_note')
            ? (string) session('mally_order_note', '')
            : '';

        $activePromo = $service->getPromoCode();
        if ($activePromo) {
            $this->promoCode = $activePromo;
            $this->appliedPromo = $activePromo;
            $this->discountUsd = (float) ($cartDetails['discount_usd'] ?? 0.0);
        } else {
            $this->promoCode = '';
            $this->appliedPromo = null;
            $this->discountUsd = 0.0;
        }

        $customer = $service->getCurrentCustomer();
        if ($customer) {
            $this->fillFromCustomer($customer);
        }
    }

    public function onCustomerAuthenticated(Customer $customer): void
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

        $rawEmail = trim((string) ($customer->email ?? ''));
        $this->email = ($rawEmail !== '' && ! str_ends_with(strtolower($rawEmail), '@telegram.mally.local'))
            ? $rawEmail
            : '';

        if ($customer->instagram && str_contains($customer->instagram, '|')) {
            [$c, $a] = array_map('trim', explode('|', $customer->instagram, 2));
            if ($c !== '') {
                $this->city = $c;
            }
            if ($a !== '') {
                $this->address = $a;
            }
        } elseif ($customer->instagram) {
            $this->address = trim((string) $customer->instagram);
        }

        if (trim($this->city) === '') {
            $lastOrderCity = Order::where('customer_id', $customer->id)
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->latest('id')
                ->value('city');
            if (is_string($lastOrderCity) && trim($lastOrderCity) !== '') {
                $this->city = trim($lastOrderCity);
            }
        }
    }

    public function applyPromo(): void
    {
        if (trim($this->promoCode) === '') {
            $this->dispatch('toast', message: 'Введите промокод', type: 'error');

            return;
        }

        $service = app(StorefrontService::class);
        $cartDetails = $service->getCartItemsWithProducts();
        $res = $service->applyPromoCode($this->promoCode, $cartDetails['subtotal_usd']);

        if (is_array($res) && ($res['success'] ?? false)) {
            $this->appliedPromo = $res['code'];
            $this->discountUsd = (float) ($res['discountUsd'] ?? 0.0);
        } else {
            $this->appliedPromo = null;
            $this->discountUsd = 0.0;
            $this->dispatch('toast', message: $res['error'] ?? 'Промокод не найден', type: 'error');
        }
    }

    public function placeOrder(): void
    {
        $service = app(StorefrontService::class);
        $customer = $service->getCurrentCustomer();

        if (! $customer) {
            $this->dispatch('toast', message: 'Для оформления заказа необходимо войти или зарегистрироваться', type: 'error');
            $this->openAuthModal('Для оформления заказа необходимо войти или зарегистрироваться');

            return;
        }

        $cart = $service->getCart();
        $this->appliedPromo = $service->getPromoCode();

        if (empty($cart)) {
            $this->dispatch('toast', message: 'Ваша корзина пуста', type: 'error');
            $this->redirect(route('cart'));

            return;
        }

        if (trim($this->firstName) === '' || trim($this->address) === '') {
            $this->dispatch('toast', message: 'Пожалуйста, укажите имя и адрес доставки', type: 'error');

            return;
        }

        $isPhoneVerified = ! empty($customer->telegram) && trim((string) $customer->phone_number) !== '';
        $rawCustEmail = trim((string) ($customer->email ?? ''));
        $hasVerifiedEmail = $rawCustEmail !== '' && ! str_ends_with(strtolower($rawCustEmail), '@telegram.mally.local');

        $rawPhone = $isPhoneVerified
            ? trim((string) $customer->phone_number)
            : trim((string) ($this->phoneBilling ?: $customer->phone_number));

        $formattedPhone = $service->normalizeAndValidateUzPhone($rawPhone) ?? ($rawPhone !== '' && $rawPhone !== '+998' ? $rawPhone : null);
        if (! $formattedPhone) {
            $this->dispatch('toast', message: 'Введите корректный номер телефона Узбекистана (+998 и 9 цифр)', type: 'error');

            return;
        }
        $this->phoneBilling = $formattedPhone;

        $emailToSave = $rawCustEmail;
        if (! $hasVerifiedEmail && trim($this->email) !== '') {
            $candEmail = mb_strtolower(trim($this->email));
            if (filter_var($candEmail, FILTER_VALIDATE_EMAIL) === false) {
                $this->dispatch('toast', message: 'Введите корректный адрес электронной почты (Gmail)', type: 'error');

                return;
            }
            $emailToSave = $candEmail;
            $this->email = $candEmail;
        }

        try {
            $rate = $service->getExchangeRate();
            if ($rate <= 0) {
                throw new \RuntimeException('Курс валют временно недоступен. Пожалуйста, попробуйте чуть позже.');
            }

            $retryService = app(OrderNumberRetryService::class);

            $order = $retryService->run(function (int $orderNumber) use ($cart, $customer, $rate, $formattedPhone, $emailToSave, $service) {
                $fullAddress = trim($this->address);
                $customerCity = trim($this->city);
                if ($customerCity === '' && $customer->instagram && str_contains($customer->instagram, '|')) {
                    [$savedCity] = array_map('trim', explode('|', $customer->instagram, 2));
                    $customerCity = $savedCity;
                }
                if ($customerCity === '') {
                    $customerCity = 'Ташкент';
                }

                $customerFullName = trim($this->firstName.' '.$this->lastName) ?: 'Покупатель';

                $customerUpdates = [
                    'first_name' => trim($this->firstName) ?: $customer->first_name,
                    'last_name' => trim($this->lastName) ?: $customer->last_name,
                    'phone_number' => $formattedPhone,
                    'instagram' => $customerCity.' | '.$fullAddress,
                ];
                if ($emailToSave !== '') {
                    $customerUpdates['email'] = $emailToSave;
                }
                $customer->update($customerUpdates);

                $secureHash = bin2hex(random_bytes(16));

                $newOrder = Order::create([
                    'customer_id' => $customer->id,
                    'order_number' => $orderNumber,
                    'secure_hash' => $secureHash,
                    'customer_name' => $customerFullName,
                    'customer_phone' => $formattedPhone,
                    'city' => $customerCity,
                    'address' => $fullAddress,
                    'comment' => trim($this->comment) ?: null,
                    'promo_code' => $this->appliedPromo,
                    'discount_amount' => 0,
                    'amount' => 0,
                    'amount_paid' => 0.00,
                    'status' => 'processing',
                    'payment_method' => 'card_fast',
                    'payment_status' => 'PENDING',
                ]);

                $verifiedSubtotalUsd = 0.0;

                foreach ($cart as $item) {
                    $prodData = $item['product'] ?? $item;
                    $prodId = $prodData['id'] ?? $item['id'] ?? null;
                    $sku = trim((string) ($prodData['sku'] ?? $item['sku'] ?? ''));
                    $title = trim((string) ($prodData['title'] ?? $prodData['name'] ?? $item['title'] ?? $item['name'] ?? 'Mally Product'));
                    $qty = (int) ($item['quantity'] ?? 0);

                    if ($qty <= 0) {
                        throw new \RuntimeException("Некорректное количество для товара «{$title}».");
                    }

                    $productModel = is_numeric($prodId) ? Product::lockForUpdate()->find((int) $prodId) : null;
                    if (! $productModel && $sku !== '') {
                        $productModel = Product::lockForUpdate()->where('sku', $sku)->first();
                    }
                    if (! $productModel && $title !== '') {
                        $productModel = Product::lockForUpdate()->where('name', $title)->first();
                    }

                    if (! $productModel) {
                        throw new \RuntimeException("Товар «{$title}» больше не доступен. Обновите корзину.");
                    }

                    $dbStock = max(0, (int) $productModel->quantity);
                    if ($productModel->status === 'out_of_stock' || $dbStock < $qty) {
                        throw new \RuntimeException("Недостаточно товара «{$productModel->name}» на складе (доступно: {$dbStock} шт.).");
                    }

                    $unitPriceUsd = (float) $productModel->selling_price;
                    if ($unitPriceUsd <= 0) {
                        throw new \RuntimeException("Некорректная цена для товара «{$productModel->name}».");
                    }

                    $verifiedSubtotalUsd += round($unitPriceUsd * $qty, 2);
                    $unitPriceUzs = $service->convertUsdToUzs($unitPriceUsd);
                    $lineTotalUzs = $unitPriceUzs * $qty;

                    $receipt = ProductReceipt::where('product_id', $productModel->id)
                        ->where('quantity_remaining', '>=', $qty)
                        ->orderBy('id', 'asc')
                        ->first();

                    OrderProduct::create([
                        'order_id' => $newOrder->id,
                        'product_id' => $productModel->id,
                        'product_receipt_id' => $receipt?->id,
                        'quantity' => $qty,
                        'price' => $unitPriceUzs,
                        'unit_cost' => $receipt?->unit_cost ?? 0,
                        'total_price' => $lineTotalUzs,
                    ]);
                }

                $discountUsd = $this->appliedPromo === 'MALLY10'
                    ? round($verifiedSubtotalUsd * 0.10, 2)
                    : 0.0;
                $finalUsd = max(0.0, round($verifiedSubtotalUsd - $discountUsd, 2));
                $finalUzs = round(($finalUsd * $rate) / 1000) * 1000;
                $discountUzs = $service->convertUsdToUzs($discountUsd);

                $newOrder->update([
                    'discount_amount' => $discountUzs,
                    'amount' => $finalUzs,
                ]);

                return $newOrder;
            });

            $service->clearCart();
            session()->forget(['mally_promo_code', 'mally_order_note']);

            $this->redirect(route('checkout.receipt', [
                'orderNumber' => $order->order_number,
                'orderHash' => $order->secure_hash,
            ]), navigate: true);
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage() ?: 'Ошибка оформления заказа', type: 'error');
        }
    }

    public function render()
    {
        $service = app(StorefrontService::class);
        $cartDetails = $service->getCartItemsWithProducts();
        $subtotalUsd = $cartDetails['subtotal_usd'];
        $this->appliedPromo = $cartDetails['promo_code'];
        $this->discountUsd = (float) ($cartDetails['discount_usd'] ?? 0.0);
        $finalTotalUsd = max(0, round($subtotalUsd - $this->discountUsd, 2));

        return view('livewire.storefront.checkout-page', array_merge($this->sharedViewData(), [
            'cartItems' => $cartDetails['items'],
            'subtotalUsd' => $subtotalUsd,
            'discountUsd' => $this->discountUsd,
            'finalTotalUsd' => $finalTotalUsd,
        ]));
    }
}
