<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return __('New order');
    }

    /** The total is calculated from the lines once they are saved. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['amount'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->recalculateAmount();
    }
}
