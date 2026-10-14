<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Services\StorefrontService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ProfilePage extends Component
{
    use InteractsWithStore;

    public string $activeTab = 'orders';

    public string $firstName = '';

    public string $lastName = '';

    public string $phone = '';

    public string $city = 'Ташкент';

    public string $address = '';

    // Review modal state
    public ?array $reviewTarget = null;

    public int $reviewRating = 5;

    public string $reviewComment = '';

    public string $reviewPhotoUrl = '';

    public array $reviewPhotos = [];

    public function mount(): void
    {
        $customer = app(StorefrontService::class)->getCurrentCustomer();
        if ($customer) {
            $this->loadCustomerFields($customer);
        }
    }

    public function onCustomerAuthenticated(Customer $customer): void
    {
        $this->loadCustomerFields($customer);
    }

    protected function loadCustomerFields(Customer $customer): void
    {
        $this->firstName = (string) ($customer->first_name ?? '');
        $this->lastName = (string) ($customer->last_name ?? '');
        $this->phone = (string) ($customer->phone_number ?? '');
        $this->city = (string) ($customer->city ?: 'Ташкент');
        $this->address = (string) ($customer->address ?? '');
    }

    public function saveSettings(): void
    {
        $customer = app(StorefrontService::class)->getCurrentCustomer();
        if (! $customer) {
            return;
        }

        if (trim($this->firstName) === '') {
            $this->dispatch('toast', message: 'Имя обязательно', type: 'error');

            return;
        }

        $cityVal = trim($this->city) ?: 'Ташкент';
        $this->city = $cityVal;

        if (mb_strlen($this->firstName) > 100 || mb_strlen($this->lastName) > 100 || mb_strlen($cityVal) > 100 || mb_strlen($this->address) > 500) {
            $this->dispatch('toast', message: __('Message is too long.'), type: 'error');

            return;
        }

        $customer->update([
            'first_name' => trim($this->firstName),
            'last_name' => trim($this->lastName),
            'city' => $cityVal,
            'address' => trim($this->address) ?: null,
        ]);
    }

    public function openProductReview(string $productId, string $title, string $image, int $orderNumber): void
    {
        $this->reviewTarget = [
            'type' => 'PRODUCT',
            'productId' => $productId,
            'title' => $title,
            'image' => $image,
            'orderNumber' => $orderNumber,
        ];
        $this->reviewRating = 5;
        $this->reviewComment = '';
        $this->reviewPhotoUrl = '';
        $this->reviewPhotos = [];
    }

    public function closeReviewModal(): void
    {
        $this->reviewTarget = null;
    }

    public function addReviewPhoto(): void
    {
        $url = trim($this->reviewPhotoUrl);
        if ($url !== '' && str_starts_with($url, 'http')) {
            $this->reviewPhotos[] = $url;
            $this->reviewPhotoUrl = '';
        }
    }

    public function removeReviewPhoto(int $index): void
    {
        unset($this->reviewPhotos[$index]);
        $this->reviewPhotos = array_values($this->reviewPhotos);
    }

    public function submitReview(): void
    {
        $locale = $this->store()->getLocale();
        $commentText = trim($this->reviewComment);

        if (! $this->reviewTarget || $commentText === '') {
            $emptyMsg = match ($locale) {
                'uz' => 'Iltimos, sharh matnini kiriting',
                'en' => 'Please enter your review text',
                default => 'Напишите текст отзыва',
            };
            $this->dispatch('toast', message: $emptyMsg, type: 'error');

            return;
        }

        $customer = app(StorefrontService::class)->getCurrentCustomer();
        if (! $customer) {
            $authMsg = match ($locale) {
                'uz' => 'Sharh qoldirish uchun tizimga kiring',
                'en' => 'Please sign in to leave a review',
                default => 'Войдите в профиль, чтобы оставить отзыв',
            };
            $this->dispatch('toast', message: $authMsg, type: 'error');

            return;
        }

        if (! Schema::hasTable('reviews')) {
            $unavailableMsg = match ($locale) {
                'uz' => "Sharhlarni saqlash vaqtincha mavjud emas (ma'lumotlar bazasi ulanmagan).",
                'en' => 'Saving reviews is currently unavailable (database storage is not configured).',
                default => 'Сохранение отзывов временно недоступно: база отзывов ещё не подключена.',
            };
            $this->dispatch('toast', message: $unavailableMsg, type: 'error');

            return;
        }

        $pendingUrl = trim($this->reviewPhotoUrl);
        if ($pendingUrl !== '' && str_starts_with($pendingUrl, 'http')) {
            $this->reviewPhotos[] = $pendingUrl;
            $this->reviewPhotoUrl = '';
        }

        $rawProdId = (string) ($this->reviewTarget['productId'] ?? '');
        $targetTitle = trim((string) ($this->reviewTarget['title'] ?? ''));
        $orderNumber = isset($this->reviewTarget['orderNumber']) ? (int) $this->reviewTarget['orderNumber'] : null;

        $dbProduct = is_numeric($rawProdId) ? Product::find((int) $rawProdId) : null;
        if (! $dbProduct && $rawProdId !== '') {
            $dbProduct = Product::where('sku', $rawProdId)->first();
        }
        if (! $dbProduct && $targetTitle !== '') {
            $dbProduct = Product::where('name', $targetTitle)->first();
        }

        $storeProduct = app(StorefrontService::class)->findProduct($dbProduct?->sku ?: ($rawProdId ?: $targetTitle));
        $resolvedSku = $dbProduct?->sku ?: ($storeProduct['sku'] ?? ($rawProdId !== '' ? $rawProdId : null));
        $resolvedTitle = $dbProduct?->name ?: ($targetTitle ?: ($storeProduct['title'] ?? 'Mally Beauty'));

        $authorName = trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) ?: 'Покупатель Mally';
        $authorCity = trim($this->city) ?: 'Ташкент';

        Review::create([
            'product_id' => $dbProduct?->id,
            'product_sku' => $resolvedSku,
            'product_title' => $resolvedTitle,
            'customer_id' => $customer->id,
            'order_number' => $orderNumber,
            'author_name' => $authorName,
            'city' => $authorCity,
            'rating' => max(1, min(5, (int) $this->reviewRating)),
            'comment' => $commentText,
            'photos' => array_values(array_unique($this->reviewPhotos)),
            'is_approved' => true,
        ]);

        Cache::forget('mally_all_products_v2');

        $this->reviewTarget = null;
        $this->reviewRating = 5;
        $this->reviewComment = '';
        $this->reviewPhotoUrl = '';
        $this->reviewPhotos = [];
    }

    public function render()
    {
        $service = app(StorefrontService::class);
        $customer = $service->getCurrentCustomer();
        $orders = collect();
        $reviewedKeys = [];

        if ($customer) {
            $orders = Order::with('orderProducts.product')
                ->where('customer_id', $customer->id)
                ->orWhere('customer_phone', $customer->phone_number)
                ->latest('id')
                ->get();

            if (Schema::hasTable('reviews')) {
                $existingReviews = Review::where('customer_id', $customer->id)->get();
                foreach ($existingReviews as $rev) {
                    $ordKey = (int) ($rev->order_number ?? 0);
                    if ($rev->product_id) {
                        $reviewedKeys[$ordKey.':'.$rev->product_id] = true;
                    }
                    if ($rev->product_sku) {
                        $reviewedKeys[$ordKey.':'.$rev->product_sku] = true;
                    }
                }
            }
        }

        return view('livewire.storefront.profile-page', array_merge($this->sharedViewData(), [
            'customer' => $customer,
            'orders' => $orders,
            'reviewedKeys' => $reviewedKeys,
        ]));
    }
}
