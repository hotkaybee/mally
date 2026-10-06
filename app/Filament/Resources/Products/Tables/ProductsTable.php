<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label(__('category.name'))
                    // `category.name` is a locale-aware accessor, so searching
                    // and sorting target the real per-locale columns instead.
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas(
                        'category',
                        fn (Builder $categoryQuery) => $categoryQuery->where(
                            fn (Builder $q) => collect(Category::nameColumns())->each(
                                fn (string $column) => $q->orWhere($column, 'like', "%{$search}%")
                            )
                        )
                    ))
                    ->sortable(query: fn (Builder $query, string $direction) => $query
                        ->orderBy(
                            Category::select(Category::nameColumn())
                                ->whereColumn('categories.id', 'products.category_id'),
                            $direction
                        )),
                TextColumn::make('brand.name')
                    ->label(__('brand.name'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('name'))
                    ->searchable(),
                TextColumn::make('sku')
                    ->label(__('sku'))
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('quantity')
                    ->label(__('quantity'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->label(__('selling_price'))
                    ->money('USD')
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
