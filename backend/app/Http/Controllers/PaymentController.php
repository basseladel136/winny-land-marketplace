<?php

namespace App\Http\Controllers;

use App\Contracts\HandlesWebhooks;
use App\Models\Order;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PaymentManager $payments) {}

    public function initiate(Request $request, string $orderNumber): JsonResponse
    {
        $order = $request->user()
            ->orders()
            ->where('order_number', $orderNumber)
            ->where('payment_status', Order::PAYMENT_PENDING)
            ->firstOrFail();

        $gateway = $this->payments->gateway($order->payment_method);

        return response()->json(['data' => $gateway->initiate($order)]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $gateway = $this->payments->gateway('paymob');

        if (! $gateway instanceof HandlesWebhooks) {
            return response()->json(['message' => 'Unsupported.'], 400);
        }

        $hmac = $request->query('hmac', '');
        $data = $request->all();

        if (! $gateway->verifyWebhook($data, $hmac)) {
            // SECURITY: Never log the raw HMAC value — it is a shared secret.
            // Log a truncated fingerprint for correlation only.
            Log::warning('Paymob webhook HMAC verification failed', [
                'hmac_prefix' => $hmac ? substr($hmac, 0, 8) . '...' : 'empty',
                'ip'          => $request->ip(),
            ]);
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $gateway->handleWebhook($data);

        return response()->json(['message' => 'OK']);
    }
}
