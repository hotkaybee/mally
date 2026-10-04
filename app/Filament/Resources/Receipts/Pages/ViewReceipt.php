<?php

namespace App\Filament\Resources\Receipts\Pages;

use App\Filament\Resources\Receipts\ReceiptResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReceipt extends ViewRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
