<?php

namespace App\Http\Controllers;

use App\Traits\ai;
use Illuminate\Support\Facades\Http;

class WeatherRecommendationController extends Controller
{
    use ai;

    public function index()
    {
        try {
            // 1. Fetch sales data for the last 7 days
            $sales = $this->getOrderFor7Days('No sales data found in the last 7 days.');

            // 2. Get current weather from OpenWeather API
            $city = 'Riyadh';
            $weatherApiKey = env('OPENWEATHER_API_KEY');

            $weatherResponse = Http::get('https://api.openweathermap.org/data/2.5/weather', [
                'q' => $city,
                'appid' => $weatherApiKey,
                'units' => 'metric',
            ]);

            if (!$weatherResponse->successful()) {
                return response()->json([
                    'error' => 'Failed to fetch weather data.',
                    'details' => $weatherResponse->json(),
                ], 500);
            }

            $weatherData = $weatherResponse->json();
            $temp = $weatherData['main']['temp'] ?? null;

            if (is_null($temp)) {
                return response()->json([
                    'error' => 'Temperature data not available.',
                ], 500);
            }

            // 3. Build sales and weather context
            $salesJson = $sales->toJson(JSON_PRETTY_PRINT);

            $weatherSuggestion = match (true) {
                $temp >= 30 => "Promote cold drinks due to hot weather ({$temp}°C).",
                $temp <= 15 => "Promote hot drinks due to cold weather ({$temp}°C).",
                default => "Moderate weather ({$temp}°C), promote regular products."
            };

            $dynamicPricingSuggestion = match (true) {
                $temp >= 30 => "Consider discounting cold drinks and slightly increasing prices for hot drinks.",
                $temp <= 15 => "Consider discounting hot drinks and slightly increasing prices for cold drinks.",
                default => "Maintain regular pricing."
            };

            $prompt = <<<EOT
                    Given the following sales data:
                    $salesJson

                    Weather information: Current temperature in $city is {$temp}°C.
                    $weatherSuggestion
                    $dynamicPricingSuggestion

                    Which products should we promote to increase revenue and why? Provide specific strategic suggestions including dynamic pricing based on the weather or seasonality.
                    EOT;

            // 4. Send prompt to OpenAI API
            $recommendation = $this->operationAi(env('OPENAI_API_KEY'),$prompt);

            // 5. Return response
            return response()->json([
                'temperature' => $temp,
                'weatherSuggestion' => $weatherSuggestion,
                'dynamicPricingSuggestion' => $dynamicPricingSuggestion,
                'recommendation' => $recommendation,
            ]);



        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while generating the recommendation.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
