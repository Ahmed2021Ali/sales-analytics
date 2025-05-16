<?php

namespace App\Http\Controllers;

use App\Traits\analyticsData;

class AnalyticsController extends Controller
{
    use analyticsData;


    public function index()
    {
        return response()->json($this->getAnalyticsData());
    }

}
