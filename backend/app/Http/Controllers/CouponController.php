<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyCouponRequest;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function __construct(private CouponService $service) {}

    public function validate(ApplyCouponRequest $request): JsonResponse
    {
        $result = $this->service->validate(
            $request->validated('code'),
            (float) $request->validated('orderTotal')
        );

        return response()->json(['data' => $result]);
    }
}
