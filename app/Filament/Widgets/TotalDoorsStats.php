<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalDoorsStats extends BaseWidget
{
    protected function getStats(): array
    {
        $doorsCountDay = Order::query()->whereDate('date', Carbon::today())->sum('doors_count');

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $doorsCountWeek = Order::query()->whereBetween('date', [$startOfWeek, $endOfWeek])->sum('doors_count');

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $doorsCountMonth = Order::query()->whereBetween('date', [$startOfMonth, $endOfMonth])->sum('doors_count');

        return [
            Stat::make(__('dashboard.widget.todays_doors'), $doorsCountDay)
                ->description('MDF eshiklar soni'),
            Stat::make(__('dashboard.widget.week_doors'), $doorsCountWeek)
                ->description('MDF eshiklar soni'),
            Stat::make(__('dashboard.widget.month_doors'), $doorsCountMonth)
                ->description('MDF eshiklar soni'),
        ];
    }
}
