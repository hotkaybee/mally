<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\ProductBatch;
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
                            ->options(\App\Models\Product::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set) {
                                // Reset the batch-dependent fields when the product changes.
                                $set('product_batch_id', null);
                                $set('price', null);
                                $set('total_price', null);
                            }),

                        Select::make('product_batch_id')
                            ->label(__('Batch'))
                            ->options(function (Get $get) {
                                $productId = $get('product_id');

                                if (! $productId) {
                                    return [];
                                }

                                $currentId = $get('product_batch_id');

                                return ProductBatch::query()
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
                                    ->mapWithKeys(fn (ProductBatch $b) => [
                                        $b->id => sprintf(
                                            '$%s → $%s · %s: %d · %s',
                                            number_format((float) $b->purchase_price, 2),
                                            number_format((float) $b->selling_price, 2),
                                            __('quantity_remaining'),
                                            $b->quantity_remaining,
                                            optional($b->received_at)->format('Y-m-d') ?? '—',
                                        ),
                                    ])
                                    ->toArray();
                            })
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $batch = $state ? ProductBatch::find($state) : null;

                                if ($batch) {
                                    $set('price', $batch->selling_price);
                                    $quantity = $get('quantity') ?: 1;
                                    $set('total_price', $batch->selling_price * $quantity);
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
                            // batch's remaining. On edit the InventoryService releases the
                            // line's original units before re-consuming, so we don't cap.
                            ->maxValue(function (Get $get, string $operation) {
                                if ($operation !== 'create') {
                                    return null;
                                }

                                $batch = ($id = $get('product_batch_id')) ? ProductBatch::find($id) : null;

                                return $batch?->quantity_remaining;
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
