<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // One field per locale — these names are shown to customers on
                // the public site, so each language needs its own value.
                TextInput::make('name_en')
                    ->label(__('name_en'))
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name_ru')
                    ->label(__('name_ru')),
                TextInput::make('name_uz')
                    ->label(__('name_uz')),
                TextInput::make('icon')
                    ->label(__('icon')),
            ]);
    }
}
