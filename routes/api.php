<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\OrderController;
use \App\Http\Controllers\AnalyticsController;
use \App\Http\Controllers\AIRecommendationController;
use \App\Http\Controllers\WeatherRecommendationController;



Route::post('/orders', [OrderController::class, 'store']);

Route::get('/analytics', [AnalyticsController::class, 'index']);

Route::get('/ai-recommendation', [AIRecommendationController::class, 'index']);

Route::get('/weather-recommendation', [WeatherRecommendationController::class, 'index']);


