<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ProductForm
{
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
                    ->default(0),
                TextInput::make('price_original')
                    ->label(__('price_original'))
                    ->required()
                    ->numeric(),
                TextInput::make('markup')
                    ->label(__('markup'))
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('selling_price')
                    ->label(__('selling_price'))
                    ->required()
                    ->numeric(),
                TextInput::make('website_price')
                    ->label(__('website_price'))
                    ->required()
                    ->numeric(),
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
