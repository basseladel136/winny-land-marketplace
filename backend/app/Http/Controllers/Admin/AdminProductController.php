<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function __construct(private ProductService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'category', 'search', 'minPrice', 'maxPrice',
            'featured', 'sort', 'perPage', 'active',
        ]);

        $products = $this->service->list($filters, adminMode: true);

        return response()->json(
            ProductResource::collection($products)->response()->getData(true)
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->create($request->validated());

        return response()->json([
            'data' => new ProductResource($product->load('category')),
        ], 201);
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->service->find($slug, adminMode: true);

        return response()->json([
            'data' => new ProductResource($product->load('category')),
        ]);
    }

    public function update(UpdateProductRequest $request, string $slug): JsonResponse
    {
        $product = $this->service->update($slug, $request->validated());

        return response()->json([
            'data' => new ProductResource($product->load('category')),
        ]);
    }

    public function destroy(string $slug): JsonResponse
    {
        $this->service->delete($slug);

        return response()->json(['message' => 'Product deleted.']);
    }
}
