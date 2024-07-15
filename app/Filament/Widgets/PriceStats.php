<?php

namespace App\Filament\Widgets;

use App\Models\Price;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PriceStats extends BaseWidget
{
    protected function getStats(): array
    {
        $priceAmountDay = Price::query()->whereDate('date', Carbon::today())->sum('price');

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $priceAmountWeek = Price::query()->whereBetween('date', [$startOfWeek, $endOfWeek])->sum('price');

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $priceAmountMonth = Price::query()->whereBetween('date', [$startOfMonth, $endOfMonth])->sum('price');

        return [
            Stat::make(__('dashboard.widget.payments_day'), formatPrice($priceAmountDay)),
            Stat::make(__('dashboard.widget.payments_week'), formatPrice($priceAmountWeek)),
            Stat::make(__('dashboard.widget.payments_month'), formatPrice($priceAmountMonth)),
        ];
    }
}
