<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('product_title')
                    ->label(__('Product'))
                    ->disabled(),
                TextInput::make('order_number')
                    ->label(__('Order number'))
                    ->disabled(),
                TextInput::make('author_name')
                    ->label(__('Author'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('rating')
                    ->label(__('Rating'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->required(),
                Textarea::make('comment')
                    ->label(__('comment'))
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                Textarea::make('photos')
                    ->label(__('Photos'))
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : $state)
                    ->dehydrated(false)
                    ->disabled()
                    ->columnSpanFull(),
                Toggle::make('is_approved')
                    ->label(__('Published')),
            ]);
    }
}
