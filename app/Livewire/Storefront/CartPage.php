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

    public function updatedOrderNote(string $value): void
    {
        $trimmed = trim($value);
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
            $this->openAuthModal('Войдите или зарегистрируйтесь для оформления заказа');

            return;
        }

        $trimmedNote = trim($this->orderNote);
        if ($trimmedNote !== '') {
            session(['mally_order_note' => $trimmedNote]);
        } else {
            session()->forget('mally_order_note');
        }

        $validation = $service->validateAndSyncCart();
        if (! $validation['valid']) {
            $msg = implode(' ', $validation['messages']);
            $this->dispatch('toast', message: $msg ?: 'Данные корзины были обновлены в соответствии с актуальным наличием и ценами.', type: 'error');

            return;
        }

        if (empty($validation['cart']['items'])) {
            $this->dispatch('toast', message: 'Ваша корзина пуста', type: 'error');

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
