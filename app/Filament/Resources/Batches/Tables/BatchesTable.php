<?php

namespace App\Filament\Resources\Batches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('received_at', 'desc')
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('Product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->label(__('purchase_price'))
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('delivery_cost')
                    ->label(__('delivery_cost'))
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->label(__('selling_price'))
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('markup')
                    ->label(__('markup'))
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label(__('quantity'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('quantity_remaining')
                    ->label(__('quantity_remaining'))
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('received_at')
                    ->label(__('received_at'))
                    ->date()
                    ->sortable(),
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
