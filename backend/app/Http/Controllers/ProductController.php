<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'category', 'search', 'minPrice', 'maxPrice',
            'featured', 'sort', 'perPage', 'page',
        ]);

        $products = $this->service->list($filters);

        return response()->json(
            ProductResource::collection($products)->response()->getData(true)
        );
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->service->find($slug);

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }
}
