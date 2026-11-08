<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReceipt;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
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
                    ->required()
                    // An order stays with the customer it was placed for: it shows
                    // in their profile and they hold its receipt link. A wrong
                    // customer means deleting the order and creating it again.
                    // A hidden field isn't saved, so edit shows the name as text.
                    ->hiddenOn('edit'),

                TextEntry::make('customer_name')
                    ->label(__('customer_id'))
                    ->state(fn (Order $record) => trim($record->customer?->first_name.' '.$record->customer?->last_name))
                    ->url(fn (Order $record) => $record->customer_id
                        ? CustomerResource::getUrl('view', ['record' => $record->customer_id])
                        : null)
                    ->placeholder('—')
                    ->visibleOn('edit'),

                Repeater::make('orderProducts')
                    ->label(__('Products in the order'))
                    ->relationship('orderProducts')
                    ->schema([
                        Select::make('product_id')
                            ->label(__('Product'))
                            ->options(Product::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->columnSpan(2)
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

                        // Shown while editing; the saved value is price × quantity
                        // from OrderProductObserver.
                        TextInput::make('total_price')
                            ->label(__('total_price'))
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(6)
                    ->columnSpanFull()
                    ->defaultItems(1)
                    ->addActionLabel(__('Add Product'))
                    ->collapsible(),

                // Calculated on the server from the lines (Order::recalculateAmount).
                TextInput::make('amount')
                    ->label(__('amount'))
                    ->numeric()
                    ->disabled()
                    ->helperText(__('Calculated from the products when you save.')),
                TextInput::make('amount_paid')
                    ->label(__('amount_paid'))
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('status')
                    ->label(__('status'))
                    ->options(OrderStatus::class)
                    ->default(OrderStatus::Processing)
                    ->required()
                    ->helperText(__('A cancelled order returns its products to stock.'))
                    // Bringing a cancelled order back takes its products again.
                    ->rule(fn (?Order $record) => function (string $attribute, $value, Closure $fail) use ($record) {
                        $value = $value instanceof OrderStatus ? $value : OrderStatus::tryFrom((string) $value);

                        if ($record?->isCancelled() && $value !== OrderStatus::Cancelled && ! $record->hasStockToRestore()) {
                            $fail(__('There is not enough stock left to bring this order back.'));
                        }
                    }),
            ]);
    }
}
