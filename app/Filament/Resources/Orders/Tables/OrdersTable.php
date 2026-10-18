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
            ->columns([
                TextColumn::make('customer.first_name')
                    ->label(__('Customer'))
                    ->formatStateUsing(fn ($record) => $record->customer->first_name.' '.$record->customer->last_name)
                    ->searchable(['customer.first_name', 'customer.last_name']),
                TextColumn::make('amount')
                    ->label(__('amount'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label(__('amount_paid'))
                    ->numeric()
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
