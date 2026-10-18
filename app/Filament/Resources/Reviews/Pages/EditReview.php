<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

    /** Only the moderation flag is saved; the customer's text stays as written. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Arr::only($data, ['is_approved']);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
