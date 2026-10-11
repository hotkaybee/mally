<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductReceipt;
use App\Services\StorefrontService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        // Перепроверяем актуальность цен и остатков товаров на сервере перед оформлением
        $validation = $service->validateAndSyncCart();
        $cartDetails = $validation['cart'];
        $this->appliedPromo = $cartDetails['promo_code'];
        $this->discountUsd = (float) ($cartDetails['discount_usd'] ?? 0.0);

        if (! $validation['valid']) {
            $detailsMsg = implode(' ', $validation['messages']);
            $this->dispatch(
                'toast',
                message: ($detailsMsg ? $detailsMsg.' ' : '').'Пожалуйста, проверьте обновлённые данные заказа и подтвердите его снова.',
                type: 'error'
            );

            if (empty($cartDetails['items'])) {
                $this->redirect(route('cart'));
            }

            return;
        }

        if (empty($cartDetails['items'])) {
            $this->dispatch('toast', message: 'Ваша корзина пуста', type: 'error');
            $this->redirect(route('cart'));

            return;
        }

        if (trim($this->firstName) === '' || trim($this->address) === '') {
            $this->dispatch('toast', message: 'Пожалуйста, укажите имя и адрес доставки', type: 'error');

            return;
        }

        // Всегда закрепляем номер телефона из профиля авторизованного клиента
        $rawPhone = trim((string) ($customer->phone_number ?: $this->phoneBilling));
        $formattedPhone = $service->normalizeAndValidateUzPhone($rawPhone) ?? ($rawPhone !== '' ? $rawPhone : null);
        if (! $formattedPhone) {
            $this->dispatch('toast', message: 'Введите корректный номер телефона Узбекистана (+998 и 9 цифр)', type: 'error');

            return;
        }
        $this->phoneBilling = $formattedPhone;

        try {
            $rate = $service->getExchangeRate();
            if ($rate <= 0) {
                throw new \RuntimeException('Курс валют временно недоступен. Пожалуйста, попробуйте чуть позже.');
            }

            $order = DB::transaction(function () use ($cartDetails, $customer, $rate, $formattedPhone) {
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

                $customer->update([
                    'first_name' => trim($this->firstName) ?: $customer->first_name,
                    'last_name' => trim($this->lastName) ?: $customer->last_name,
                    'instagram' => $customerCity.' | '.$fullAddress,
                ]);

                $maxNum = (int) (Order::max('order_number') ?: 1000);
                $orderNumber = $maxNum + 1;
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

                foreach ($cartDetails['items'] as $item) {
                    $prodData = $item['product'] ?? [];
                    $prodId = $prodData['id'] ?? null;
                    $sku = trim((string) ($prodData['sku'] ?? ''));
                    $title = trim((string) ($prodData['title'] ?? $prodData['name'] ?? 'Mally Product'));
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
                        $availStock = max(0, (int) ($prodData['stock'] ?? $prodData['quantity'] ?? 0));
                        if ($availStock < $qty || ! ($prodData['inStock'] ?? true)) {
                            throw new \RuntimeException("Недостаточно товара «{$title}» на складе (доступно: {$availStock} шт.).");
                        }

                        $brandName = trim((string) ($prodData['brand'] ?? 'Mally Collection')) ?: 'Mally Collection';
                        $brand = Brand::firstOrCreate(['name' => $brandName]);

                        $catName = trim((string) ($prodData['category'] ?? 'Косметика')) ?: 'Косметика';
                        $category = Category::where('name_ru', $catName)
                            ->orWhere('name_en', $catName)
                            ->orWhere('name_uz', $catName)
                            ->first()
                            ?? Category::first()
                            ?? Category::create([
                                'name_ru' => $catName,
                                'name_en' => $prodData['category_en'] ?? $catName,
                                'name_uz' => $prodData['category_uz'] ?? $catName,
                            ]);

                        $productModel = Product::create([
                            'brand_id' => $brand->id,
                            'category_id' => $category->id,
                            'name' => $title,
                            'sku' => $sku !== '' ? $sku : ('ML-'.Str::upper(Str::random(6))),
                            'description' => $prodData['description'] ?? null,
                            'quantity' => $availStock,
                            'selling_price' => (float) ($prodData['price'] ?? 35),
                            'image' => $prodData['image'] ?? null,
                            'status' => 'in_stock',
                        ]);
                    } else {
                        $dbStock = max(0, (int) $productModel->quantity);
                        if ($productModel->status === 'out_of_stock' || $dbStock < $qty) {
                            throw new \RuntimeException("Недостаточно товара «{$productModel->name}» на складе (доступно: {$dbStock} шт.).");
                        }
                    }

                    $dbNumPrice = (float) $productModel->selling_price;
                    $unitPriceUsd = ($dbNumPrice > 1000 && $rate > 0)
                        ? round($dbNumPrice / $rate, 2)
                        : ($dbNumPrice > 0 ? $dbNumPrice : (float) ($prodData['price'] ?? 35));

                    $verifiedSubtotalUsd += round($unitPriceUsd * $qty, 2);
                    $unitPriceUzs = round($unitPriceUsd * $rate);
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
                        'unit_cost' => $receipt?->purchase_price ?? 0,
                        'total_price' => $lineTotalUzs,
                    ]);

                    if (! $receipt) {
                        $productModel->decrement('quantity', $qty);
                    }
                }

                $discountUsd = $this->appliedPromo === 'MALLY10'
                    ? round($verifiedSubtotalUsd * 0.10, 2)
                    : 0.0;
                $finalUsd = max(0.0, round($verifiedSubtotalUsd - $discountUsd, 2));
                $finalUzs = round(($finalUsd * $rate) / 1000) * 1000;
                $discountUzs = round($discountUsd * $rate);

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
