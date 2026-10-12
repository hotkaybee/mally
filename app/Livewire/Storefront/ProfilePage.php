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

    public string $email = '';

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
        $this->phone = (string) ($customer->phone_number ?: '+998 ');

        $rawEmail = trim((string) ($customer->email ?? ''));
        $this->email = ($rawEmail !== '' && ! str_ends_with(strtolower($rawEmail), '@telegram.mally.local'))
            ? $rawEmail
            : '';

        if ($customer->instagram && str_contains($customer->instagram, '|')) {
            [$c, $a] = array_map('trim', explode('|', $customer->instagram, 2));
            $this->city = $c ?: 'Ташкент';
            $this->address = $a;
        } else {
            if ($customer->instagram) {
                $this->address = trim((string) $customer->instagram);
            }
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

    public function startTelegramPhoneVerification(): void
    {
        $this->isAuthModalOpen = true;
        $this->startTelegramAuth();
    }

    public function saveSettings(): void
    {
        $service = app(StorefrontService::class);
        $customer = $service->getCurrentCustomer();
        if (! $customer) {
            return;
        }

        if (trim($this->firstName) === '') {
            $this->dispatch('toast', message: 'Имя обязательно для заполнения', type: 'error');

            return;
        }

        $isPhoneVerified = ! empty($customer->telegram);
        $rawCustomerEmail = trim((string) ($customer->email ?? ''));
        $hasVerifiedEmail = $rawCustomerEmail !== '' && ! str_ends_with(strtolower($rawCustomerEmail), '@telegram.mally.local');

        $normalizedPhone = (string) $customer->phone_number;
        if (! $isPhoneVerified) {
            $validPhone = $service->normalizeAndValidateUzPhone($this->phone);
            if (! $validPhone) {
                $this->dispatch('toast', message: 'Введите корректный номер телефона (+998 и 9 цифр)', type: 'error');

                return;
            }
            $normalizedPhone = $validPhone;
            $this->phone = $normalizedPhone;
        }

        $emailToSave = $rawCustomerEmail;
        if (! $hasVerifiedEmail && trim($this->email) !== '') {
            $candidateEmail = mb_strtolower(trim($this->email));
            if (filter_var($candidateEmail, FILTER_VALIDATE_EMAIL) === false) {
                $this->dispatch('toast', message: 'Введите корректный адрес электронной почты (Gmail)', type: 'error');

                return;
            }

            $currentUserId = (int) session('mally_user_id', 0);
            $emailTaken = \App\Models\User::whereRaw('LOWER(email) = ?', [$candidateEmail])
                ->when($currentUserId > 0, fn ($q) => $q->where('id', '!=', $currentUserId))
                ->exists();

            if ($emailTaken) {
                $this->dispatch('toast', message: 'Этот email уже привязан к другому аккаунту.', type: 'error');

                return;
            }

            $emailToSave = $candidateEmail;
            $this->email = $candidateEmail;
        }

        $cityVal = trim($this->city) ?: 'Ташкент';
        $this->city = $cityVal;

        $customer->update([
            'first_name' => trim($this->firstName),
            'last_name' => trim($this->lastName),
            'phone_number' => $normalizedPhone,
            'email' => $emailToSave,
            'instagram' => $cityVal.' | '.trim($this->address),
        ]);

        $userId = (int) session('mally_user_id', 0);
        $user = $userId > 0 ? \App\Models\User::find($userId) : null;
        if ($user) {
            $userUpdates = [
                'name' => trim(trim($this->firstName).' '.trim($this->lastName)),
                'phone' => $normalizedPhone,
            ];
            if (! $hasVerifiedEmail && $emailToSave !== '' && ! str_ends_with(strtolower($emailToSave), '@telegram.mally.local')) {
                $userUpdates['email'] = $emailToSave;
            }
            $user->update($userUpdates);
        }

        $this->dispatch('toast', message: 'Личные данные успешно сохранены!', type: 'success');
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
