<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    public function index()
    {
        $recentSales = DB::select('SELECT product_id, quantity, price, order_date FROM orders ORDER BY order_date DESC LIMIT 10');

        $salesData = json_encode($recentSales);

        $prompt = "Given this sales data, which products should we promote for higher revenue? Sales data: {$salesData}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.api_key'),
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 200,
        ]);

        $suggestions = $response->json()['choices'][0]['message']['content'] ?? 'No suggestions';

        return response()->json(['recommendations' => $suggestions]);
    }
}
