<?php

namespace App\Services\Payment;

use App\Contracts\HandlesWebhooks;
use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobGateway implements PaymentGateway, HandlesWebhooks
{
    private string $apiKey;
    private string $integrationId;
    private string $iframeId;
    private string $hmacSecret;
    private string $baseUrl = 'https://accept.paymob.com/api';

    public function __construct()
    {
        $this->apiKey        = config('paymob.api_key');
        $this->integrationId = config('paymob.integration_id');
        $this->iframeId      = config('paymob.iframe_id');
        $this->hmacSecret    = config('paymob.hmac_secret');
    }

    public function initiate(Order $order): array
    {
        $authToken = $this->getAuthToken();

        // Step 2: Register order
        $paymobOrderId = $this->registerOrder($authToken, $order);

        // Step 3: Get payment key
        $paymentKey = $this->getPaymentKey($authToken, $paymobOrderId, $order);

        $iframeUrl = "https://accept.paymob.com/api/acceptance/iframes/{$this->iframeId}?payment_token={$paymentKey}";

        return [
            'paymentKey' => $paymentKey,
            'iframeUrl'  => $iframeUrl,
        ];
    }

    private function getAuthToken(): string
    {
        return Cache::remember('paymob:auth_token', 3000, function () {
            $response = Http::post("{$this->baseUrl}/auth/tokens", [
                'api_key' => $this->apiKey,
            ]);

            if (! $response->successful()) {
                throw new \Exception('Paymob auth failed: ' . $response->body());
            }

            return $response->json('token');
        });
    }

    private function registerOrder(string $authToken, Order $order): int
    {
        $items = $order->items->map(fn ($item) => [
            'name'        => $item->product_name,
            'amount_cents'=> (int) ($item->price * 100),
            'description' => '',
            'quantity'    => $item->quantity,
        ])->toArray();

        $response = Http::post("{$this->baseUrl}/ecommerce/orders", [
            'auth_token'        => $authToken,
            'delivery_needed'   => false,
            'amount_cents'      => (int) ($order->total * 100),
            'currency'          => 'EGP',
            'merchant_order_id' => $order->order_number,
            'items'             => $items,
        ]);

        if (! $response->successful()) {
            throw new \Exception('Paymob order registration failed: ' . $response->body());
        }

        return $response->json('id');
    }

    private function getPaymentKey(string $authToken, int $paymobOrderId, Order $order): string
    {
        $response = Http::post("{$this->baseUrl}/acceptance/payment_keys", [
            'auth_token'     => $authToken,
            'amount_cents'   => (int) ($order->total * 100),
            'expiration'     => 3600,
            'order_id'       => $paymobOrderId,
            'billing_data'   => [
                'apartment'      => 'N/A',
                'email'          => $order->customer_email,
                'floor'          => 'N/A',
                'first_name'     => $order->customer_name,
                'street'         => $order->shipping_address,
                'building'       => 'N/A',
                'phone_number'   => $order->customer_phone ?? 'N/A',
                'shipping_method'=> 'N/A',
                'postal_code'    => 'N/A',
                'city'           => 'N/A',
                'country'        => 'EG',
                'last_name'      => '',
                'state'          => 'N/A',
            ],
            'currency'       => 'EGP',
            'integration_id' => $this->integrationId,
            'lock_order_when_paid' => false,
        ]);

        if (! $response->successful()) {
            throw new \Exception('Paymob payment key failed: ' . $response->body());
        }

        return $response->json('token');
    }

    public function verifyWebhook(array $data, string $hmac): bool
    {
        $fields = [
            'amount_cents', 'created_at', 'currency', 'error_occured',
            'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
            'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
            'is_voided', 'order', 'owner', 'pending', 'source_data_pan',
            'source_data_sub_type', 'source_data_type', 'success',
        ];

        $obj = $data['obj'] ?? [];
        $str = '';
        foreach ($fields as $field) {
            if ($field === 'order') {
                $str .= $obj['order']['id'] ?? '';
            } elseif ($field === 'pending') {
                $str .= $obj[$field] ? 'true' : 'false';
            } elseif (in_array($field, ['error_occured','has_parent_transaction','is_3d_secure','is_auth','is_capture','is_refunded','is_standalone_payment','is_voided','success'])) {
                $str .= isset($obj[$field]) ? ($obj[$field] ? 'true' : 'false') : '';
            } else {
                $str .= $obj[$field] ?? '';
            }
        }

        $computed = hash_hmac('sha512', $str, $this->hmacSecret);
        return hash_equals($computed, $hmac);
    }

    public function handleWebhook(array $data): void
    {
        $obj         = $data['obj'] ?? [];
        $orderNumber = $obj['order']['merchant_order_id'] ?? null;
        $success     = $obj['success'] ?? false;
        $transId     = (string) ($obj['id'] ?? '');
        $amountCents = (int) ($obj['amount_cents'] ?? 0);

        if (! $orderNumber || $transId === '') return;

        $order = Order::where('order_number', $orderNumber)->first();
        if (! $order) return;

        // Idempotency: skip if this exact transaction was already recorded
        if ($order->payment_reference === $transId) {
            Log::info('Paymob webhook already processed', ['trans_id' => $transId]);
            return;
        }

        // Reject webhooks where the charged amount does not match the order total
        if ($success && $amountCents !== (int) ($order->total * 100)) {
            Log::error('Paymob webhook amount mismatch — possible fraud', [
                'order'    => $orderNumber,
                'expected' => (int) ($order->total * 100),
                'received' => $amountCents,
            ]);
            return;
        }

        // Use direct attribute assignment to bypass $fillable on payment fields
        $order->payment_status    = $success ? Order::PAYMENT_PAID : Order::PAYMENT_FAILED;
        $order->payment_reference = $transId;
        $order->save();

        if ($success && $order->status === Order::STATUS_PENDING) {
            $order->status = Order::STATUS_PROCESSING;
            $order->save();
        }

        Log::info('Paymob webhook processed', ['order' => $orderNumber, 'success' => $success]);

        if ($success) {
            AnalyticsService::clearCache();
        }
    }
}
