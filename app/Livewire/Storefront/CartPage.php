<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Services\StorefrontService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CartPage extends Component
{
    use InteractsWithStore;

    public string $orderNote = '';

    public function mount(): void
    {
        if (session()->has('mally_order_note')) {
            $this->orderNote = (string) session('mally_order_note', '');
        }
    }

    /** Same limit as the checkout comment. */
    private const MAX_NOTE_LENGTH = 1000;

    public function updatedOrderNote(string $value): void
    {
        $this->rememberNote($value);
    }

    private function rememberNote(string $value): void
    {
        $trimmed = mb_substr(trim($value), 0, self::MAX_NOTE_LENGTH);
        if ($trimmed !== '') {
            session(['mally_order_note' => $trimmed]);
        } else {
            session()->forget('mally_order_note');
        }
    }

    public function proceedToCheckout(): void
    {
        $service = $this->store();
        $customer = $service->getCurrentCustomer();
        if (! $customer) {
            $this->openAuthModal(__('Please sign in or register to place an order'));

            return;
        }

        $this->rememberNote($this->orderNote);

        $validation = $service->validateAndSyncCart();
        if (! $validation['valid']) {
            $msg = implode(' ', $validation['messages']);
            $this->dispatch('toast', message: $msg ?: __('Please review your order and confirm it again.'), type: 'error');

            return;
        }

        if (empty($validation['cart']['items'])) {
            $this->dispatch('toast', message: __('Your cart is empty.'), type: 'error');

            return;
        }

        $this->redirect(route('checkout'), navigate: true);
    }

    public function render()
    {
        $service = app(StorefrontService::class);
        $cartDetails = $service->getCartItemsWithProducts();

        return view('livewire.storefront.cart-page', array_merge($this->sharedViewData(), [
            'cartItems' => $cartDetails['items'],
            'subtotalUsd' => $cartDetails['subtotal_usd'],
            'totalCount' => collect($cartDetails['items'])->sum('quantity'),
        ]));
    }
}
