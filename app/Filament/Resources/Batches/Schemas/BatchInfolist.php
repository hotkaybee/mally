<?php

namespace App\Filament\Resources\Batches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('product.name')
                    ->label(__('Product'))
                    ->placeholder('-'),
                TextEntry::make('received_at')
                    ->label(__('received_at'))
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('purchase_price')
                    ->label(__('purchase_price'))
                    ->money('USD'),
                TextEntry::make('delivery_cost')
                    ->label(__('delivery_cost'))
                    ->money('USD'),
                TextEntry::make('markup')
                    ->label(__('markup'))
                    ->suffix('%'),
                TextEntry::make('selling_price')
                    ->label(__('selling_price'))
                    ->money('USD'),
                TextEntry::make('website_price')
                    ->label(__('website_price'))
                    ->money('USD'),
                TextEntry::make('quantity')
                    ->label(__('quantity'))
                    ->numeric(),
                TextEntry::make('quantity_remaining')
                    ->label(__('quantity_remaining'))
                    ->numeric(),
                TextEntry::make('comment')
                    ->label(__('comment'))
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}
