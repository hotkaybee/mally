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
                    ->relationship('category', 'name'),
                Select::make('brand_id')
                    ->relationship('brand', 'name'),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Textarea::make('comment')
                    ->columnSpanFull(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('price_original')
                    ->required()
                    ->numeric(),
                TextInput::make('markup')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('selling_price')
                    ->required()
                    ->numeric(),
                TextInput::make('website_price')
                    ->required()
                    ->numeric(),
                FileUpload::make('image')
                    ->image(),
                TextInput::make('link'),
                Select::make('status')
                    ->options(['in_stock' => 'In stock', 'out_of_stock' => 'Out of stock', 'on_the_way' => 'On the way'])
                    ->default('in_stock')
                    ->required(),
            ]);
    }
}
