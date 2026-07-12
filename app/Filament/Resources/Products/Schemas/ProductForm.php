<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductForm
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
                Select::make('category_id')
                    ->label(__('category_id'))
                    ->relationship('category', 'name'),
                Select::make('brand_id')
                    ->label(__('brand_id'))
                    ->relationship('brand', 'name'),
                TextInput::make('name')
                    ->label(__('name'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('description'))
                    ->columnSpanFull(),
                Textarea::make('comment')
                    ->label(__('comment'))
                    ->columnSpanFull(),
                TextInput::make('quantity')
                    ->label(__('quantity'))
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->columnSpanFull(),
                TextInput::make('website_price')
                    ->label(__('website_price'))
                    ->required()
                    ->numeric(),
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
                    ->disabled()
                    ->dehydrated()
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->label(__('image'))
                    ->image(),
                TextInput::make('link')
                    ->label(__('link')),
                Select::make('status')
                    ->label(__('status'))
                    ->options([
                        'in_stock' => __('In stock'),
                        'out_of_stock' => __('Out of stock'),
                        'on_the_way' => __('On the way')
                    ])
                    ->default('in_stock')
                    ->required(),
            ]);
    }
}
