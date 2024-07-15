<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class PaymentPercentage extends ChartWidget
{
    protected static ?string $heading = 'Mijozlarni to\'lovlari statistikasi';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '335px';

    protected function getData(): array
    {

        $notPaymentCustomersCount = Order::query()->whereDoesntHave('prices')->count();

        $advancePaymentCustomersCount = Order::whereHas('prices', function ($query) {
            $query->select('order_id')
                ->groupBy('order_id')
                ->havingRaw('SUM(price) < orders.total_price');
        })->count();

        $fullPaymentCustomersCount = Order::whereHas('prices', function ($query) {
            $query->select('order_id')
                ->groupBy('order_id')
                ->havingRaw('SUM(price) >= orders.total_price');
        })->count();

        return [
            'datasets' => [
                [
                    'data' => [$fullPaymentCustomersCount, $advancePaymentCustomersCount, $notPaymentCustomersCount],
                    'borderColor' => '#111827',
                    'backgroundColor' => [
                        '#2563eb',
                        '#facc15',
                        '#991b1b',
                    ],
                ],
            ],
            'labels' => ['To\'lab bo\'lganlar', 'Avans berganlar', 'To\'lov qilmaganlar'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
