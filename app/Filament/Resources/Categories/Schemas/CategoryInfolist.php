<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name_en')
                    ->label(__('name_en')),
                TextEntry::make('name_ru')
                    ->label(__('name_ru'))
                    ->placeholder('-'),
                TextEntry::make('name_uz')
                    ->label(__('name_uz'))
                    ->placeholder('-'),
                TextEntry::make('icon')
                    ->label(__('icon'))
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
