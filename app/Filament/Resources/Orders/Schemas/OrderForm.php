<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductReceipt;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label(__('customer_id'))
                    ->relationship('customer', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->first_name.' '.$record->last_name)
                    ->searchable(['first_name', 'last_name'])
                    ->required(),

                Repeater::make('orderProducts')
                    ->relationship('orderProducts')
                    ->schema([
                        Select::make('product_id')
                            ->label(__('Product'))
                            ->options(Product::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set) {
                                // Reset the receipt-dependent fields when the product changes.
                                $set('product_receipt_id', null);
                                $set('price', null);
                                $set('total_price', null);
                            }),

                        Select::make('product_receipt_id')
                            ->label(__('Receipt'))
                            ->options(function (Get $get) {
                                $productId = $get('product_id');

                                if (! $productId) {
                                    return [];
                                }

                                $currentId = $get('product_receipt_id');

                                return ProductReceipt::query()
                                    ->where('product_id', $productId)
                                    ->where(function ($q) use ($currentId) {
                                        $q->where('quantity_remaining', '>', 0);

                                        if ($currentId) {
                                            $q->orWhere('id', $currentId);
                                        }
                                    })
                                    ->orderByDesc('received_at')
                                    ->orderByDesc('id')
                                    ->get()
                                    ->mapWithKeys(fn (ProductReceipt $receipt) => [
                                        $receipt->id => sprintf(
                                            '$%s → $%s · %s: %d · %s',
                                            number_format((float) $receipt->purchase_price, 2),
                                            number_format((float) $receipt->selling_price, 2),
                                            __('quantity_remaining'),
                                            $receipt->quantity_remaining,
                                            optional($receipt->received_at)->format('Y-m-d') ?? '—',
                                        ),
                                    ])
                                    ->toArray();
                            })
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $receipt = $state ? ProductReceipt::find($state) : null;

                                if ($receipt) {
                                    $set('price', $receipt->selling_price);
                                    $quantity = $get('quantity') ?: 1;
                                    $set('total_price', $receipt->selling_price * $quantity);
                                }
                            }),

                        TextInput::make('quantity')
                            ->label(__('quantity'))
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->reactive()
                            // Cap at what the receipt can give. An existing line already
                            // holds its units (they are returned before re-drawing), so
                            // those count as available for the same receipt.
                            ->maxValue(function (Get $get, $record) {
                                $receipt = ($id = $get('product_receipt_id')) ? ProductReceipt::find($id) : null;
                                if (! $receipt) {
                                    return null;
                                }

                                $held = $record instanceof OrderProduct && (int) $record->getOriginal('product_receipt_id') === (int) $receipt->id
                                    ? (int) $record->getOriginal('quantity')
                                    : 0;

                                return (int) $receipt->quantity_remaining + $held;
                            })
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $price = $get('price') ?: 0;
                                $set('total_price', $price * ($state ?: 1));
                            }),

                        TextInput::make('price')
                            ->label(__('price'))
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $quantity = $get('quantity') ?: 1;
                                $set('total_price', ($state ?: 0) * $quantity);
                            }),

                        TextInput::make('total_price')
                            ->label(__('total_price'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(5)
                    ->defaultItems(1)
                    ->addActionLabel(__('Add Product'))
                    ->collapsible(),

                // Computed on the server from the saved lines minus the discount.
                TextInput::make('amount')
                    ->label(__('amount'))
                    ->prefix('$')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText(__('Calculated from the order lines')),
                TextInput::make('amount_paid')
                    ->label(__('amount_paid'))
                    ->prefix('$')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0.0),
                Select::make('status')
                    ->label(__('status'))
                    ->options([
                        'processing' => __('Processing'),
                        'awaiting_delivery' => __('Awaiting delivery'),
                        'delivered' => __('Delivered'),
                    ])
                    ->default('processing')
                    ->required(),
            ]);
    }
}
