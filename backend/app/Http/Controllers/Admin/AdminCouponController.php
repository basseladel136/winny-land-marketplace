<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $coupons = Coupon::latest()->paginate($request->integer('perPage', 20));

        return response()->json(
            CouponResource::collection($coupons)->response()->getData(true)
        );
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $data = $request->validated();

        $coupon = Coupon::create([
            'code'             => strtoupper($data['code']),
            'type'             => $data['type'],
            'value'            => $data['value'],
            'min_order_amount' => $data['minOrderAmount'] ?? 0,
            'max_uses'         => $data['maxUses'] ?? null,
            'is_active'        => $data['isActive'] ?? true,
            'expires_at'       => $data['expiresAt'] ?? null,
        ]);

        return response()->json([
            'data' => new CouponResource($coupon),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => new CouponResource(Coupon::findOrFail($id)),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'type'           => 'sometimes|in:percent,fixed',
            'value'          => 'sometimes|numeric|min:0',
            'minOrderAmount' => 'sometimes|numeric|min:0',
            'maxUses'        => 'sometimes|nullable|integer|min:1',
            'isActive'       => 'sometimes|boolean',
            'expiresAt'      => 'sometimes|nullable|date',
        ]);

        $coupon = Coupon::findOrFail($id);
        $coupon->update(array_filter([
            'type'             => $data['type'] ?? null,
            'value'            => $data['value'] ?? null,
            'min_order_amount' => $data['minOrderAmount'] ?? null,
            'max_uses'         => $data['maxUses'] ?? null,
            'is_active'        => $data['isActive'] ?? null,
            'expires_at'       => $data['expiresAt'] ?? null,
        ], fn ($v) => ! is_null($v)));

        return response()->json([
            'data' => new CouponResource($coupon->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        Coupon::findOrFail($id)->delete();

        return response()->json(['message' => 'Coupon deleted.']);
    }
}
