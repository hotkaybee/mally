<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Services\StorefrontService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
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

    private const MAX_REVIEW_PHOTOS = 5;

    // Review modal state (set by openProductReview after an ownership check)
    #[Locked]
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

    protected function onCustomerAuthenticated(Customer $customer): void
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
        $order = $this->reviewableOrder((int) $productId, $orderNumber);
        if (! $order) {
            $this->dispatch('toast', message: __('You can only review products from your delivered orders.'), type: 'error');

            return;
        }

        $product = Product::find((int) $productId);

        // Target data comes from the database, not from the browser.
        $this->reviewTarget = [
            'type' => 'PRODUCT',
            'productId' => (string) $product->id,
            'title' => $product->name,
            'image' => $image,
            'orderNumber' => (int) $order->order_number,
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
        if ($this->isAllowedPhotoUrl($url) && count($this->reviewPhotos) < self::MAX_REVIEW_PHOTOS) {
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
        $commentText = trim($this->reviewComment);

        if (! $this->reviewTarget || $commentText === '') {
            $this->dispatch('toast', message: __('Please enter your review text'), type: 'error');

            return;
        }

        if (mb_strlen($commentText) > 2000) {
            $this->dispatch('toast', message: __('Message is too long.'), type: 'error');

            return;
        }

        $customer = app(StorefrontService::class)->getCurrentCustomer();
        if (! $customer) {
            $this->dispatch('toast', message: __('Please sign in to leave a review'), type: 'error');

            return;
        }

        $productId = (int) ($this->reviewTarget['productId'] ?? 0);
        $orderNumber = (int) ($this->reviewTarget['orderNumber'] ?? 0);

        // Re-check on the server: the order is the customer's, delivered, and contains the product.
        $order = $this->reviewableOrder($productId, $orderNumber);
        $product = $order ? Product::find($productId) : null;
        if (! $order || ! $product) {
            $this->reviewTarget = null;
            $this->dispatch('toast', message: __('You can only review products from your delivered orders.'), type: 'error');

            return;
        }

        $alreadyReviewed = Review::whereHas('order', fn ($q) => $q->where('customer_id', $customer->id))
            ->where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->exists();
        if ($alreadyReviewed) {
            $this->reviewTarget = null;
            $this->dispatch('toast', message: __('You have already reviewed this product for this order.'), type: 'error');

            return;
        }

        $pendingUrl = trim($this->reviewPhotoUrl);
        if ($pendingUrl !== '') {
            $this->reviewPhotos[] = $pendingUrl;
        }
        $photos = array_slice(array_values(array_unique(array_filter($this->reviewPhotos, fn ($url) => $this->isAllowedPhotoUrl((string) $url)))), 0, self::MAX_REVIEW_PHOTOS);

        Review::create([
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => max(1, min(5, (int) $this->reviewRating)),
            'comment' => $commentText,
            'photos' => $photos,
            // Published only after an admin approves it in the panel.
            'is_approved' => false,
        ]);

        $this->reviewTarget = null;
        $this->reviewRating = 5;
        $this->reviewComment = '';
        $this->reviewPhotoUrl = '';
        $this->reviewPhotos = [];

        $this->dispatch('toast', message: __('Thank you! Your review will appear after moderation.'), type: 'success');
    }

    /**
     * The signed-in customer's delivered order with this number that contains the product.
     */
    private function reviewableOrder(int $productId, int $orderNumber): ?Order
    {
        $customer = app(StorefrontService::class)->getCurrentCustomer();
        if (! $customer || $productId <= 0 || $orderNumber <= 0) {
            return null;
        }

        return Order::where('customer_id', $customer->id)
            ->withNumber($orderNumber)
            ->where('status', 'delivered')
            ->whereHas('orderProducts', fn ($q) => $q->where('product_id', $productId))
            ->first();
    }

    private function isAllowedPhotoUrl(string $url): bool
    {
        return mb_strlen($url) <= 500
            && str_starts_with($url, 'https://')
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
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
                ->latest('id')
                ->get();

            $reviews = Review::whereHas('order', fn ($q) => $q->where('customer_id', $customer->id))
                ->get(['order_id', 'product_id']);
            foreach ($reviews as $rev) {
                $reviewedKeys[$rev->order_id.':'.$rev->product_id] = true;
            }
        }

        return view('livewire.storefront.profile-page', array_merge($this->sharedViewData(), [
            'customer' => $customer,
            'orders' => $orders,
            'reviewedKeys' => $reviewedKeys,
        ]));
    }
}
