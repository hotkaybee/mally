<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make(__('Total Orders'), Order::count())
                ->description(__('All time orders'))
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),

            // Money actually received, not the total of unpaid orders.
            Stat::make(__('Total Revenue'), '$'.number_format((float) Order::sum('amount_paid'), 2))
                ->description(__('Payments received'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make(__('Total Customers'), Customer::count())
                ->description(__('Registered customers'))
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make(__('Total Products'), Product::count())
                ->description(__('Products in inventory'))
                ->descriptionIcon('heroicon-m-cube')
                ->color('warning'),
        ];
    }
}
