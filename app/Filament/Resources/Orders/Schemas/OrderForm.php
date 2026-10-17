<?php

namespace App\Filament\Resources\Orders\Schemas;

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
                            // On the order create page stock is accurate, so cap at the
                            // receipt's remaining. On edit the InventoryService releases the
                            // line's original units before re-consuming, so we don't cap.
                            ->maxValue(function (Get $get, string $operation) {
                                if ($operation !== 'create') {
                                    return null;
                                }

                                $receipt = ($id = $get('product_receipt_id')) ? ProductReceipt::find($id) : null;

                                return $receipt?->quantity_remaining;
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

                TextInput::make('amount')
                    ->label(__('amount'))
                    ->required()
                    ->numeric(),
                TextInput::make('amount_paid')
                    ->label(__('amount_paid'))
                    ->required()
                    ->numeric()
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
