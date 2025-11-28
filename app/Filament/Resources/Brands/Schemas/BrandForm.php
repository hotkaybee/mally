<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('name'))
                    ->required(),
                TextInput::make('website')
                    ->label(__('website'))
                    ->url(),
                TextInput::make('logo')
                    ->label(__('Logo URL'))
                    ->url(),
            ]);
    }
}
