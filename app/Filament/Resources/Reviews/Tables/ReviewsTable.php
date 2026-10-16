<?php

namespace App\Filament\Resources\Reviews\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product', 'order.customer']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('Product'))
                    ->searchable()
                    ->limit(40),
                // author_name is computed from the order's customer.
                TextColumn::make('author_name')
                    ->label(__('Author'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas(
                        'order.customer',
                        fn (Builder $customerQuery) => $customerQuery->where(
                            fn (Builder $q) => $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                        )
                    )),
                TextColumn::make('rating')
                    ->label(__('Rating'))
                    ->sortable(),
                TextColumn::make('comment')
                    ->label(__('comment'))
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('order.order_number')
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
