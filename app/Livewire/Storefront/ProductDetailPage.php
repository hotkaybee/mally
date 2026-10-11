<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ProductDetailPage extends Component
{
    use InteractsWithStore;

    public string $slug = '';

    public function mount(string $slug = ''): void
    {
        $this->slug = $slug;
    }

    public function render()
    {
        $store = $this->store();
        $product = $store->findProduct($this->slug);
        if (! $product) {
            abort(404);
        }

        $recommendedProducts = $store->getRecommendedProducts($product, 4);

        return view('livewire.storefront.product-detail-page', array_merge($this->sharedViewData(), [
            'product' => $product,
            'recommendedProducts' => $recommendedProducts,
        ]));
    }
}
