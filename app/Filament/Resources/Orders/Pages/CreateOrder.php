<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\OrderService;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Filled from the lines in afterCreate().
        $data['amount'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        app(OrderService::class)->recalculateAmount($this->record);
    }
}
