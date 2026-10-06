<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label(__('category_id'))
                    // `name` is a locale-aware accessor, not a column, so the
                    // label is resolved from the record rather than queried.
                    ->relationship('category', Category::nameColumn())
                    ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->name)
                    ->searchable()
                    ->preload(),
                Select::make('brand_id')
                    ->label(__('brand_id'))
                    ->relationship('brand', 'name'),
                TextInput::make('name')
                    ->label(__('name'))
                    ->required(),
                TextInput::make('sku')
                    ->label(__('sku'))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label(__('description'))
                    ->columnSpanFull(),
                Textarea::make('comment')
                    ->label(__('comment'))
                    ->columnSpanFull(),
                // quantity and selling_price are caches maintained from receipts
                // (App\Services\InventoryService); shown here read-only.
                TextInput::make('quantity')
                    ->label(__('quantity'))
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText(__('Maintained from receipts')),
                TextInput::make('selling_price')
                    ->label(__('selling_price'))
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText(__('From the latest receipt')),
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
                        'on_the_way' => __('On the way'),
                    ])
                    ->default('in_stock')
                    ->required(),
            ]);
    }
}
