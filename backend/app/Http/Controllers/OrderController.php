<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $service) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json(
            OrderResource::collection($orders)->response()->getData(true)
        );
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->service->create($request->user(), $request->validated());

        return response()->json([
            'data' => new OrderResource($order->load('items')),
        ], 201);
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = $request->user()
            ->orders()
            ->with('items')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }
}
