<?php

namespace App\Traits;



use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait analyticsData{

    public function getAnalyticsData()
    {
        $now = Carbon::now();

        $oneMinuteAgo = $now->copy()->subMinute();

        $totalRevenue = DB::selectOne('SELECT SUM(price * quantity) as total_revenue FROM orders')->total_revenue ?? 0;

        $topProducts = DB::select('
                            SELECT product_id, SUM(quantity) as total_quantity
                            FROM orders
                            GROUP BY product_id
                            ORDER BY total_quantity DESC
                            LIMIT 5
                    ');

        $twoMinutesAgo = $now->copy()->subMinutes(2);

        $revenueLastMinute = DB::selectOne('
                                SELECT SUM(price * quantity) as revenue_1min
                                FROM orders
                                WHERE order_date >= ?
                            ', [$oneMinuteAgo])->revenue_1min ?? 0;

        $revenuePrevMinute = DB::selectOne('
                                SELECT SUM(price * quantity) as revenue_prev_1min
                                FROM orders
                                WHERE order_date BETWEEN ? AND ?
                            ', [$twoMinutesAgo, $oneMinuteAgo])->revenue_prev_1min ?? 0;

        $revenueChange = $revenueLastMinute - $revenuePrevMinute;

        $ordersCount = DB::selectOne('SELECT COUNT(*) as count FROM orders WHERE order_date >= ?', [$oneMinuteAgo])->count ?? 0;

        return [
            'total_revenue' => (float) $totalRevenue,
            'top_products' => array_map(fn($item) => $item->product_id, $topProducts),
            'revenue_change_last_minute' => (float) $revenueChange,
            'orders_count_last_minute' => $ordersCount,
        ];
    }

}

