<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Services\StorefrontService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class FavoritesPage extends Component
{
    use InteractsWithStore;

    public function render()
    {
        $service = app(StorefrontService::class);
        $favoriteProducts = $service->getFavoriteProducts();

        return view('livewire.storefront.favorites-page', array_merge($this->sharedViewData(), [
            'favoriteProducts' => $favoriteProducts,
        ]));
    }
}
