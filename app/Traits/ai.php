<?php

namespace App\Traits;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

trait ai
{

    public function getOrderFor7Days($message)
    {
        // 1. Fetch sales data for the last 7 days
        $sales = DB::table('orders')
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(price * quantity) as total_revenue')
            )
            ->where('order_date', '>=', now()->subDays(7))
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->get();

        if ($sales->isEmpty()) {
            return response()->json([
                'recommendation' => $message,
            ]);
        }
        return $sales;
    }

    public function operationAi($API_KEY,$prompt)
    {
        // 4. Send prompt to OpenAI API
        $aiResponse = Http::withToken($API_KEY)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a sales strategist AI.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
            ]);

        if (!$aiResponse->successful()) {
            return response()->json([
                'error' => 'Failed to get a response from OpenAI.',
                'details' => $aiResponse->json(),
            ], $aiResponse->status());
        }

        // 5. Return response
        return $aiResponse->json('choices.0.message.content') ?? 'No recommendation returned.';

    }

}

