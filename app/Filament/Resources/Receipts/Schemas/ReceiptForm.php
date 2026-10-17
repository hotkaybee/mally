<?php

namespace App\Filament\Resources\Receipts\Schemas;

use App\Models\ProductReceipt;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ReceiptForm
{
    protected static function updateSellingPrice(Get $get, Set $set): void
    {
        $purchasePrice = (float) $get('purchase_price');
        $deliveryCost = (float) $get('delivery_cost');
        $markup = (float) $get('markup');

        $sellingPrice = $purchasePrice + ($purchasePrice * $markup / 100) + $deliveryCost;

        $set('selling_price', round($sellingPrice, 2));
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label(__('Product'))
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    // A receipt with sales belongs to that product for good.
                    ->disabled(fn (?ProductReceipt $record) => $record?->orderProducts()->exists() ?? false)
                    ->required(),
                TextInput::make('quantity')
                    ->label(__('quantity'))
                    ->required()
                    ->numeric()
                    // Can't go below the units already sold from this receipt.
                    ->minValue(fn (?ProductReceipt $record) => max(1, $record ? (int) $record->quantity - (int) $record->quantity_remaining : 1))
                    ->default(1),
                DatePicker::make('received_at')
                    ->label(__('received_at'))
                    ->default(now()),
                TextInput::make('website_price')
                    ->label(__('website_price'))
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('purchase_price')
                    ->label(__('purchase_price'))
                    ->required()
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateSellingPrice($get, $set)),
                TextInput::make('delivery_cost')
                    ->label(__('delivery_cost'))
                    ->required()
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateSellingPrice($get, $set)),
                TextInput::make('markup')
                    ->label(__('markup_percent'))
                    ->required()
                    ->numeric()
                    ->suffix('%')
                    ->default(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateSellingPrice($get, $set)),
                TextInput::make('selling_price')
                    ->label(__('selling_price'))
                    ->required()
                    ->numeric()
                    ->columnSpanFull(),
                Textarea::make('comment')
                    ->label(__('comment'))
                    ->columnSpanFull(),
            ]);
    }
}
