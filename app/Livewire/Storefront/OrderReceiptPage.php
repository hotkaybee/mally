<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OrderReceiptPage extends Component
{
    use InteractsWithStore;

    #[Locked]
    public string $orderNumber;

    #[Locked]
    public ?string $orderHash = null;

    public function mount(string $orderNumber, ?string $orderHash = null): void
    {
        $this->orderNumber = $orderNumber;
        $this->orderHash = $orderHash;
    }

    public function refreshOrderStatus(): void
    {
        // Order status is re-queried automatically on Livewire render
    }

    public function render()
    {
        abort_unless(ctype_digit($this->orderNumber), 404);

        $order = Order::with(['customer', 'orderProducts.product'])
            ->where('order_number', (int) $this->orderNumber)
            ->firstOrFail();

        // Access: the secret link from checkout, or the signed-in owner of the order.
        // A matching phone number alone is not proof of ownership.
        $customer = $this->store()->getCurrentCustomer();
        $isOwner = $customer && $order->customer_id && (int) $order->customer_id === (int) $customer->id;
        $hasValidHash = ! empty($order->secure_hash)
            && ! empty($this->orderHash)
            && hash_equals((string) $order->secure_hash, (string) $this->orderHash);

        abort_unless($isOwner || $hasValidHash, 403);

        return view('livewire.storefront.order-receipt-page', array_merge($this->sharedViewData(), [
            'order' => $order,
            'cardNumber' => (string) config('services.store.card_number'),
            'cardHolder' => (string) config('services.store.card_holder'),
            'adminTelegram' => (string) config('services.telegram.admin_username'),
        ]));
    }
}
