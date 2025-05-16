<?php

namespace App\Http\Controllers;

use App\Events\UpdateAnalyticsData;
use App\Traits\analyticsData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class OrderController extends Controller
{

    use analyticsData;

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer', 'quantity' => 'required|integer', 'price' => 'required|numeric',
        ]);

        DB::insert('INSERT INTO orders (product_id, quantity, price, order_date) VALUES (?, ?, ?, ?)', [
            $request->product_id,
            $request->quantity,
            $request->price,
            now(),
        ]);

        // TODO: analytics update here
        event(new UpdateAnalyticsData($this->getAnalyticsData()));

        return response()->json(['message' => 'Order added successfully']);
    }

}
