<?php

use Carbon\Carbon;

if (!function_exists('formatDateHuman')) {
    /**
     * Format the date of birth. Example: 1 Iyul 2024
     *
     * @param string $date
     * @return string
     */
    function formatDateHuman($date)
    {
        $months = [
            'January' => 'Yanvar',
            'February' => 'Fevral',
            'March' => 'Mart',
            'April' => 'Aprel',
            'May' => 'May',
            'June' => 'Iyun',
            'July' => 'Iyul',
            'August' => 'Avgust',
            'September' => 'Sentyabr',
            'October' => 'Oktyabr',
            'November' => 'Noyabr',
            'December' => 'Dekabr',
        ];

        $date = Carbon::parse($date);

        $month = $months[$date->format('F')];
        $day = $date->format('j');
        $year = $date->format('Y');

        return "{$day} {$month}, {$year}";
    }

    if (!function_exists('formatPrice')) {
        /**
         * Format a number with grouped thousands
         *
         * @param float $number
         * @return string
         */
        function formatPrice($number)
        {
            return number_format($number, 0, ',', ' ');
        }
    }
}
