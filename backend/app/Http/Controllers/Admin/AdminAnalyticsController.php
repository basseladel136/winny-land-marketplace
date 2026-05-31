<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AdminAnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $service) {}

    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->summary(),
        ]);
    }

    public function customers(): JsonResponse
    {
        $result = $this->service->customers();

        return response()->json(['data' => $result]);
    }
}
