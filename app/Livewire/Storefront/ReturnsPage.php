<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ReturnsPage extends Component
{
    use InteractsWithStore;

    public function render()
    {
        return view('livewire.storefront.returns-page', $this->sharedViewData());
    }
}
