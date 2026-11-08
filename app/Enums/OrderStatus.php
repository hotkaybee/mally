<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The one list of order statuses, shared by the admin, the receipt page and
 * the profile. "Paid" is not a status: it follows from amount_paid
 * (Order::isPaid()), see Order::progressStep().
 */
enum OrderStatus: string implements HasColor, HasLabel
{
    case Processing = 'processing';
    case Packing = 'packing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __(match ($this) {
            self::Processing => 'order_status.placed',
            default => 'order_status.'.$this->value,
        });
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Processing => 'warning',
            self::Packing => 'info',
            self::Shipped => 'primary',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
        };
    }
}
