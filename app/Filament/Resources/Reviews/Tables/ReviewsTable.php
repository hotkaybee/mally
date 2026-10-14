<?php

namespace App\Filament\Resources\Reviews\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('product_title')
                    ->label(__('Product'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('author_name')
                    ->label(__('Author'))
                    ->searchable(),
                TextColumn::make('rating')
                    ->label(__('Rating'))
                    ->sortable(),
                TextColumn::make('comment')
                    ->label(__('comment'))
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('order_number')
                    ->label(__('Order number'))
                    ->toggleable(),
                ToggleColumn::make('is_approved')
                    ->label(__('Published')),
                TextColumn::make('created_at')
                    ->label(__('created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_approved')
                    ->label(__('Published')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
