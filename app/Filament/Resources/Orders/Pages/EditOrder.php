<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\OrderService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            // Delivered orders are history (OrderObserver refuses to delete them).
            DeleteAction::make()->hidden(fn () => $this->record->status === 'delivered'),
        ];
    }

    /** The total always matches the saved lines. */
    protected function afterSave(): void
    {
        app(OrderService::class)->recalculateAmount($this->record);
    }
}
