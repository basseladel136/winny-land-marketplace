<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $service) {}

    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->summary(),
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        $page   = max(1, (int) $request->query('page', 1));
        $result = $this->service->customers($page);

        return response()->json(['data' => $result]);
    }
}
