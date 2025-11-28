<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MonthlyRevenueChart extends ChartWidget
{
    protected static ?int $sort = 3;

    public function getHeading(): ?string
    {
        return __('Monthly Revenue');
    }

    protected function getData(): array
    {
        $data = $this->getRevenuePerMonth();

        return [
            'datasets' => [
                [
                    'label' => __('Total Revenue'),
                    'data' => $data['revenue'],
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b',
                ],
            ],
            'labels' => $data['months'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function getRevenuePerMonth(): array
    {
        $now = Carbon::now();
        $months = [];
        $revenue = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $months[] = $month->format('M Y');

            $monthlyRevenue = Order::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');

            $revenue[] = $monthlyRevenue;
        }

        return [
            'months' => $months,
            'revenue' => $revenue,
        ];
    }
}
