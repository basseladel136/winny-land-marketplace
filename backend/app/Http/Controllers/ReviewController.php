<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $service) {}

    public function index(int $productId): JsonResponse
    {
        $reviews = $this->service->forProduct($productId);

        return response()->json(
            ReviewResource::collection($reviews)->response()->getData(true)
        );
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'body'   => 'nullable|string|max:2000',
        ]);

        $review = $this->service->create($request->user(), $productId, $data);

        return response()->json([
            'data' => new ReviewResource($review),
        ], 201);
    }

    public function update(Request $request, int $productId): JsonResponse
    {
        $data = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'body'   => 'sometimes|nullable|string|max:2000',
        ]);

        $review = $this->service->update($request->user(), $productId, $data);

        return response()->json([
            'data' => new ReviewResource($review),
        ]);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $this->service->delete($request->user(), $productId);

        return response()->json(['message' => 'Review deleted.']);
    }
}
