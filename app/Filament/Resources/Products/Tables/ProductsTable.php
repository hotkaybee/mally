<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label(__('category.name'))
                    ->searchable(),
                TextColumn::make('brand.name')
                    ->label(__('brand.name'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('name'))
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label(__('quantity'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('delivery_cost')
                    ->label(__('delivery_cost'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('markup')
                    ->label(__('markup'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->label(__('selling_price'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('website_price')
                    ->label(__('website_price'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->label(__('purchase_price'))
                    ->numeric()
                    ->sortable(),
                ImageColumn::make('image')
                    ->label(__('image')),
                TextColumn::make('link')
                    ->label(__('link'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
