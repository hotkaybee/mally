<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Low Stock Alert';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->with(['brand', 'category'])
                    ->where('quantity', '<=', 5)
                    ->where('quantity', '>', 0)
                    ->orderBy('quantity', 'asc')
            )
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 1 => 'danger',
                        $state <= 3 => 'warning',
                        $state <= 5 => 'info',
                        default => 'success',
                    }),
                TextColumn::make('selling_price')
                    ->money('USD')
                    ->sortable(),
            ])
            ->emptyStateHeading('All products are well stocked!')
            ->emptyStateDescription('No products with low stock found.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
