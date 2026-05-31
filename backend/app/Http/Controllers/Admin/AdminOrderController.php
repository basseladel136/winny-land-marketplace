<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Jobs\SendOrderStatusUpdateEmail;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'        => ['sometimes', 'string', 'in:pending,processing,shipped,delivered,cancelled'],
            'paymentStatus' => ['sometimes', 'string', 'in:pending,paid,failed,refunded,unpaid'],
            'search'        => ['sometimes', 'string', 'max:100'],
            'perPage'       => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Order::with('items')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($payment = $request->query('paymentStatus')) {
            $query->where('payment_status', $payment);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'ILIKE', "%{$search}%")
                  ->orWhere('customer_name', 'ILIKE', "%{$search}%")
                  ->orWhere('customer_email', 'ILIKE', "%{$search}%");
            });
        }

        $orders = $query->paginate($request->integer('perPage', 20));

        return response()->json(
            OrderResource::collection($orders)->response()->getData(true)
        );
    }

    public function show(string $orderNumber): JsonResponse
    {
        $order = Order::with('items')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    public function updateStatus(Request $request, string $orderNumber): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $order->update(['status' => $data['status']]);

        SendOrderStatusUpdateEmail::dispatch($order)->onQueue('emails');

        return response()->json([
            'data' => new OrderResource($order->fresh('items')),
        ]);
    }

    public function updatePaymentStatus(Request $request, string $orderNumber): JsonResponse
    {
        $data = $request->validate([
            'paymentStatus' => 'required|in:pending,paid,failed,refunded',
        ]);

        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $order->payment_status = $data['paymentStatus'];
        $order->save();

        return response()->json([
            'data' => new OrderResource($order->fresh('items')),
        ]);
    }
}
