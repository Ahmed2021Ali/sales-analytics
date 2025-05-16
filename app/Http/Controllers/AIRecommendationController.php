<?php

namespace App\Http\Controllers;

use App\Traits\ai;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class AIRecommendationController extends Controller
{
    use ai;

    public function index()
    {
        try {
            // 1. Get sales data for the last 7 days
            $sales = $this->getOrderFor7Days('Consider running promotions to boost sales');


            $currentDate = now()->format('Y-m-d');

            // 2. Try to get AI recommendation
            $aiRecommendation = $this->getAIRecommendation($sales);

            // 3. If AI fails, generate basic local recommendation
            if ($aiRecommendation === null) {
                $aiRecommendation = $this->generateLocalRecommendation($sales);
            }

            return response()->json([
                'data_analyzed' => $sales->count(),
                'time_period' => 'last 7 days',
                'generated_at' => $currentDate,
                'recommendation' => $aiRecommendation,
                'is_fallback' => ($aiRecommendation === null),
                'raw_data' => $sales
            ]);

        } catch (\Exception $e) {
            Log::error("Sales analysis failed: " . $e->getMessage());

            return response()->json([
                'error' => 'Sales analysis unavailable',
                'message' => $e->getMessage(),
                'fallback_recommendation' => 'Focus on promoting your best-selling products and consider seasonal trends.'
            ], 500);
        }
    }

    protected function getAIRecommendation($sales)
    {
        try {
            if (!env('OPENAI_API_KEY')) {
                return null;
            }

            $salesJson = $sales->toJson(JSON_PRETTY_PRINT);

            $prompt = "Analyze this sales data and recommend top 3 products to promote:\n{$salesJson}\n";
            $prompt .= "Include specific promotion strategies and reasoning.";

            $response = Http::withToken(env('OPENAI_API_KEY'))
                ->timeout(15)
                ->retry(2, 100)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a sales analyst. Provide concise recommendations.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.5,
                    'max_tokens' => 800,
                ]);

            if (!$response->successful()) {
                Log::warning("OpenAI API failed: " . $response->body());
                return null;
            }

            return $response->json('choices.0.message.content');

        } catch (Exception $e) {
            Log::warning("AI recommendation failed: " . $e->getMessage());
            return null;
        }
    }

    protected function generateLocalRecommendation($sales)
    {
        // Basic analysis without AI
        $topProducts = $sales->take(3);

        $recommendation = "Based on local analysis of sales data:\n\n";
        $recommendation .= "Top Products to Promote:\n";

        foreach ($topProducts as $product) {
            $recommendation .= "- {$product->name}: Sold {$product->total_quantity} units (Revenue: {$product->total_revenue})\n";
        }

        $recommendation .= "\nSuggestions:\n";
        $recommendation .= "1. Create featured promotions for the top products\n";
        $recommendation .= "2. Consider bundle deals for complementary products\n";
        $recommendation .= "3. Highlight these products in marketing materials\n";

        return $recommendation;
    }
}
