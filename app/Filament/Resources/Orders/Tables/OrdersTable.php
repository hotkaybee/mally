<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('customer'))
            ->columns([
                TextColumn::make('order_number')
                    ->label(__('Order number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.first_name')
                    ->label(__('Customer'))
                    // The customer may have been deleted; the order keeps the recipient name.
                    ->formatStateUsing(fn ($record) => $record->customer
                        ? $record->customer->first_name.' '.$record->customer->last_name
                        : $record->customer_name)
                    ->searchable(['customer.first_name', 'customer.last_name']),
                TextColumn::make('amount')
                    ->label(__('amount'))
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label(__('amount_paid'))
                    ->money('USD')
                    ->sortable(),
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
