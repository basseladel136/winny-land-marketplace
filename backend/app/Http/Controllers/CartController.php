<?php

namespace App\Http\Controllers;

use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $service) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->service->getOrCreate($request->user());

        return response()->json([
            'data' => new CartResource($cart->load('items.product')),
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'productId' => 'required|integer|exists:products,id',
            'quantity'  => 'sometimes|integer|min:1|max:100',
        ]);

        $cart = $this->service->addItem(
            $request->user(),
            $data['productId'],
            $data['quantity'] ?? 1
        );

        return response()->json([
            'data' => new CartResource($cart->load('items.product')),
        ]);
    }

    public function updateItem(Request $request, int $productId): JsonResponse
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $cart = $this->service->updateItem($request->user(), $productId, $data['quantity']);

        return response()->json([
            'data' => new CartResource($cart->load('items.product')),
        ]);
    }

    public function removeItem(Request $request, int $productId): JsonResponse
    {
        $cart = $this->service->removeItem($request->user(), $productId);

        return response()->json([
            'data' => new CartResource($cart->load('items.product')),
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $this->service->clear($request->user());

        return response()->json(['message' => 'Cart cleared.']);
    }

    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'             => 'required|array|max:100',
            'items.*.productId' => 'required|integer|exists:products,id',
            'items.*.quantity'  => 'required|integer|min:1|max:100',
        ]);

        $cart = $this->service->sync($request->user(), $data['items']);

        return response()->json([
            'data' => new CartResource($cart->load('items.product')),
        ]);
    }
}
