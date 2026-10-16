<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReviewForm
{
    /**
     * A moderator only publishes or hides a review; what the customer
     * wrote (rating, text, photos) is read-only.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('product_id')
                    ->label(__('Product'))
                    ->formatStateUsing(fn ($record) => $record?->product?->name)
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('order_id')
                    ->label(__('Order number'))
                    ->formatStateUsing(fn ($record) => $record?->order?->order_number)
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('author_name')
                    ->label(__('Author'))
                    ->formatStateUsing(fn ($record) => $record?->author_name)
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('rating')
                    ->label(__('Rating'))
                    ->disabled()
                    ->dehydrated(false),
                Textarea::make('comment')
                    ->label(__('comment'))
                    ->rows(5)
                    ->disabled()
                    ->dehydrated(false)
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
