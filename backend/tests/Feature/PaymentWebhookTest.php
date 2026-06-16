<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;
    private string $hmacSecret = 'test-hmac-secret-key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['paymob.hmac_secret' => $this->hmacSecret]);

        $user     = User::factory()->create();
        $category = Category::factory()->create();
        $product  = Product::factory()->create([
            'category_id' => $category->id,
            'price'       => 100.00,
            'stock'       => 10,
        ]);

        $this->order = Order::create([
            'user_id'          => $user->id,
            'order_number'     => 'WL-2026-00001',
            'status'           => Order::STATUS_PENDING,
            'subtotal'         => 200.00,
            'discount_amount'  => 0,
            'total'            => 200.00,
            'payment_method'   => 'paymob',
            'customer_name'    => 'Test User',
            'customer_email'   => 'test@example.com',
            'shipping_address' => '123 Test St',
        ]);
        $this->order->payment_status = Order::PAYMENT_UNPAID;
        $this->order->save();
    }

    private function buildWebhookPayload(array $overrides = []): array
    {
        $base = [
            'amount_cents'            => 20000,
            'created_at'              => '2026-01-01T00:00:00',
            'currency'                => 'EGP',
            'error_occured'           => false,
            'has_parent_transaction'  => false,
            'id'                      => 12345,
            'integration_id'          => 999,
            'is_3d_secure'            => false,
            'is_auth'                 => false,
            'is_capture'              => false,
            'is_refunded'             => false,
            'is_standalone_payment'   => true,
            'is_voided'               => false,
            'order'                   => ['id' => 777, 'merchant_order_id' => 'WL-2026-00001'],
            'owner'                   => 1,
            'pending'                 => false,
            'source_data_pan'         => '1234',
            'source_data_sub_type'    => 'MasterCard',
            'source_data_type'        => 'card',
            'success'                 => true,
        ];

        return ['obj' => array_merge($base, $overrides)];
    }

    private function computeHmac(array $obj): string
    {
        $fields = [
            'amount_cents', 'created_at', 'currency', 'error_occured',
            'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
            'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
            'is_voided', 'order', 'owner', 'pending', 'source_data_pan',
            'source_data_sub_type', 'source_data_type', 'success',
        ];

        $boolFields = [
            'error_occured', 'has_parent_transaction', 'is_3d_secure', 'is_auth',
            'is_capture', 'is_refunded', 'is_standalone_payment', 'is_voided',
            'success',
        ];

        $str = '';
        foreach ($fields as $field) {
            if ($field === 'order') {
                $str .= $obj['order']['id'] ?? '';
            } elseif ($field === 'pending') {
                $str .= $obj[$field] ? 'true' : 'false';
            } elseif (in_array($field, $boolFields)) {
                $str .= isset($obj[$field]) ? ($obj[$field] ? 'true' : 'false') : '';
            } else {
                $str .= $obj[$field] ?? '';
            }
        }

        return hash_hmac('sha512', $str, $this->hmacSecret);
    }

    public function test_valid_webhook_marks_order_as_paid(): void
    {
        $payload = $this->buildWebhookPayload();
        $hmac    = $this->computeHmac($payload['obj']);

        $this->postJson('/api/v1/payments/webhook?hmac=' . $hmac, $payload)
            ->assertOk();

        $this->order->refresh();
        $this->assertEquals(Order::PAYMENT_PAID, $this->order->payment_status);
        $this->assertEquals(Order::STATUS_PROCESSING, $this->order->status);
        $this->assertEquals('12345', $this->order->payment_reference);
    }

    public function test_invalid_hmac_is_rejected(): void
    {
        $payload = $this->buildWebhookPayload();

        $this->postJson('/api/v1/payments/webhook?hmac=invalid-hmac', $payload)
            ->assertStatus(401);

        $this->order->refresh();
        $this->assertEquals(Order::PAYMENT_UNPAID, $this->order->payment_status);
    }

    public function test_failed_webhook_marks_order_as_failed(): void
    {
        $payload = $this->buildWebhookPayload(['success' => false]);
        $hmac    = $this->computeHmac($payload['obj']);

        $this->postJson('/api/v1/payments/webhook?hmac=' . $hmac, $payload)
            ->assertOk();

        $this->order->refresh();
        $this->assertEquals(Order::PAYMENT_FAILED, $this->order->payment_status);
    }

    public function test_webhook_with_wrong_amount_is_rejected(): void
    {
        // Webhook claims 100 EGP was charged, but order total is 200 EGP
        $payload = $this->buildWebhookPayload(['amount_cents' => 10000]);
        $hmac    = $this->computeHmac($payload['obj']);

        $this->postJson('/api/v1/payments/webhook?hmac=' . $hmac, $payload)
            ->assertOk(); // Returns 200 but does nothing

        $this->order->refresh();
        $this->assertEquals(Order::PAYMENT_UNPAID, $this->order->payment_status);
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        $payload = $this->buildWebhookPayload();
        $hmac    = $this->computeHmac($payload['obj']);

        // Send same webhook twice
        $this->postJson('/api/v1/payments/webhook?hmac=' . $hmac, $payload)->assertOk();
        $this->postJson('/api/v1/payments/webhook?hmac=' . $hmac, $payload)->assertOk();

        // Status should still be paid (not changed by second webhook)
        $this->order->refresh();
        $this->assertEquals(Order::PAYMENT_PAID, $this->order->payment_status);
        $this->assertEquals('12345', $this->order->payment_reference);
    }

    public function test_webhook_for_nonexistent_order_returns_200(): void
    {
        $payload         = $this->buildWebhookPayload();
        $payload['obj']['order']['merchant_order_id'] = 'WL-9999-NOEXIST';
        $hmac            = $this->computeHmac($payload['obj']);

        // Should silently ignore and return 200 (Paymob expects 200 or it retries)
        $this->postJson('/api/v1/payments/webhook?hmac=' . $hmac, $payload)
            ->assertOk();
    }
}
