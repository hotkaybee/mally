<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class OrderReceiptPage extends Component
{
    use InteractsWithStore;

    public string $orderNumber;

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
        $order = Order::with(['customer', 'orderProducts.product'])
            ->where(function ($q) {
                $q->where('order_number', $this->orderNumber)
                    ->orWhere('id', is_numeric($this->orderNumber) ? (int) $this->orderNumber : 0);
            })
            ->firstOrFail();

        $customer = $this->store()->getCurrentCustomer();
        $isOwner = $customer && (
            ((int) $order->customer_id > 0 && (int) $order->customer_id === (int) $customer->id)
            || ($customer->phone_number && $order->customer_phone === $customer->phone_number)
        );

        $hasValidHash = ! empty($order->secure_hash)
            && ! empty($this->orderHash)
            && hash_equals((string) $order->secure_hash, (string) $this->orderHash);

        if (! empty($order->secure_hash)) {
            if (! $hasValidHash && ! $isOwner) {
                abort(403);
            }
        } elseif (! $isOwner) {
            abort(403);
        }

        return view('livewire.storefront.order-receipt-page', array_merge($this->sharedViewData(), [
            'order' => $order,
            'cardNumber' => '9860 1901 0114 9374',
            'cardHolder' => 'MALLY BEAUTY (Uzcard / Humo)',
            'adminTelegram' => 'mallyuz',
        ]));
    }
}
