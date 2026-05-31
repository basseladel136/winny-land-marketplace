<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(private WishlistService $service) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->service->get($request->user());

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    public function toggle(Request $request, int $productId): JsonResponse
    {
        $added = $this->service->toggle($request->user(), $productId);

        return response()->json([
            'added'   => $added,
            'message' => $added ? 'Added to wishlist.' : 'Removed from wishlist.',
        ]);
    }

    public function remove(Request $request, int $productId): JsonResponse
    {
        $this->service->remove($request->user(), $productId);

        return response()->json(['message' => 'Removed from wishlist.']);
    }
}
