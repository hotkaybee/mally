<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('customer'))
            ->columns([
                // Not a column: Order::order_number is derived from the id.
                TextColumn::make('order_number')
                    ->label(__('Order number'))
                    ->searchable(query: fn (Builder $query, string $search) => ctype_digit($search) ? $query->withNumber((int) $search) : $query)
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('id', $direction)),
                TextColumn::make('customer.first_name')
                    ->label(__('Customer'))
                    // The customer may have been deleted; the order keeps the recipient name.
                    ->formatStateUsing(fn ($record) => $record->customer
                        ? $record->customer->first_name.' '.$record->customer->last_name
                        : $record->customer_name)
                    ->searchable(['first_name', 'last_name']),
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
