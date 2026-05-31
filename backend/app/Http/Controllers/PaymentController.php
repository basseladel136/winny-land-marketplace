<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $service) {}

    public function initiate(Request $request, string $orderNumber): JsonResponse
    {
        $order = $request->user()
            ->orders()
            ->where('order_number', $orderNumber)
            ->where('payment_method', 'paymob')
            ->where('payment_status', Order::PAYMENT_PENDING)
            ->firstOrFail();

        $result = $this->service->initiatePayment($order);

        return response()->json(['data' => $result]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $hmac = $request->query('hmac', '');
        $data = $request->all();

        if (! $this->service->verifyWebhook($data, $hmac)) {
            Log::warning('Paymob webhook HMAC verification failed', ['hmac' => $hmac]);
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $this->service->handleWebhook($data);

        return response()->json(['message' => 'OK']);
    }
}
