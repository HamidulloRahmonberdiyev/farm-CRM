<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderChart extends ChartWidget
{
    protected static ?string $heading = 'Buyurtmalar diagrammasi';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $ordersData = $this->getOrdersData();

        return [
            'datasets' => [
                [
                    'label' => 'Buyurtmalar',
                    'data' =>  $ordersData,
                ],
            ],
            'labels' => ['Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentyabr', 'Oktyabr', 'Noyabr', 'Dekabr'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOrdersData(): array
    {
        $ordersCounts = [];

        for ($month = 1; $month <= 12; $month++) {
            $ordersCount = Order::whereMonth('date', $month)->sum('doors_count');
            $ordersCounts[] = $ordersCount;
        }

        return $ordersCounts;
    }
}
