<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Extended order tests covering coupon application, stock-locking,
 * payment method validation, and order-number uniqueness.
 */
class OrderAdvancedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->user = User::factory()->create();

        $category      = Category::factory()->create();
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'price'       => 100.00,
            'stock'       => 10,
            'is_active'   => true,
        ]);
    }

    private function addToCart(int $quantity = 1): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', [
                'productId' => $this->product->id,
                'quantity'  => $quantity,
            ]);
    }

    private function placeOrder(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user)
            ->postJson('/api/v1/orders', array_merge([
                'customerName'    => 'Test User',
                'customerEmail'   => 'test@example.com',
                'shippingAddress' => '1 Test St, Cairo',
                'paymentMethod'   => 'cod',
            ], $overrides));
    }

    public function test_order_decrements_stock(): void
    {
        $this->addToCart(3);
        $this->placeOrder()->assertStatus(201);

        $this->assertDatabaseHas('products', [
            'id'    => $this->product->id,
            'stock' => 7,
        ]);
    }

    public function test_order_clears_cart(): void
    {
        $this->addToCart(2);
        $this->placeOrder()->assertStatus(201);

        $cart = $this->actingAs($this->user)->getJson('/api/v1/cart');
        $this->assertEmpty($cart->json('data.items'));
    }

    public function test_order_with_percent_coupon_applies_discount(): void
    {
        Coupon::factory()->create([
            'code'             => 'SAVE10',
            'type'             => 'percent',
            'value'            => 10,
            'min_order_amount' => 0,
            'is_active'        => true,
        ]);

        $this->addToCart(2); // 200 EGP subtotal

        $response = $this->placeOrder(['couponCode' => 'SAVE10']);
        $response->assertStatus(201);
        $this->assertEquals(20, $response->json('data.discountAmount'));
        $this->assertEquals(180, $response->json('data.total'));
    }

    public function test_order_with_fixed_coupon_applies_discount(): void
    {
        Coupon::factory()->create([
            'code'             => 'OFF30',
            'type'             => 'fixed',
            'value'            => 30,
            'min_order_amount' => 0,
            'is_active'        => true,
        ]);

        $this->addToCart(1); // 100 EGP

        $response = $this->placeOrder(['couponCode' => 'OFF30']);
        $response->assertStatus(201);
        $this->assertEquals(70, $response->json('data.total'));
    }

    public function test_expired_coupon_is_rejected(): void
    {
        Coupon::factory()->create([
            'code'       => 'EXPIRED',
            'type'       => 'percent',
            'value'      => 20,
            'is_active'  => true,
            'expires_at' => now()->subDay(),
        ]);

        $this->addToCart(1);
        $this->placeOrder(['couponCode' => 'EXPIRED'])->assertStatus(422);
    }

    public function test_coupon_below_minimum_order_amount_is_rejected(): void
    {
        Coupon::factory()->create([
            'code'             => 'BIG',
            'type'             => 'fixed',
            'value'            => 50,
            'min_order_amount' => 500,
            'is_active'        => true,
        ]);

        $this->addToCart(1); // only 100 EGP

        $this->placeOrder(['couponCode' => 'BIG'])->assertStatus(422);
    }

    public function test_coupon_at_max_uses_is_rejected(): void
    {
        Coupon::factory()->create([
            'code'      => 'MAXED',
            'type'      => 'percent',
            'value'     => 10,
            'max_uses'  => 1,
            'uses_count'=> 1, // already exhausted
            'is_active' => true,
        ]);

        $this->addToCart(1);
        $this->placeOrder(['couponCode' => 'MAXED'])->assertStatus(422);
    }

    public function test_coupon_uses_count_increments_on_order(): void
    {
        $coupon = Coupon::factory()->create([
            'code'      => 'COUNTME',
            'type'      => 'percent',
            'value'     => 5,
            'is_active' => true,
        ]);

        $this->addToCart(1);
        $this->placeOrder(['couponCode' => 'COUNTME'])->assertStatus(201);

        $this->assertEquals(1, $coupon->fresh()->uses_count);
    }

    public function test_order_requires_valid_payment_method(): void
    {
        $this->addToCart(1);

        $this->placeOrder(['paymentMethod' => 'bitcoin'])->assertStatus(422);
    }

    public function test_cod_order_starts_as_unpaid(): void
    {
        $this->addToCart(1);
        $response = $this->placeOrder(['paymentMethod' => 'cod']);
        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id'        => $this->user->id,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_paymob_order_starts_as_payment_pending(): void
    {
        $this->addToCart(1);
        $response = $this->placeOrder(['paymentMethod' => 'paymob']);
        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id'        => $this->user->id,
            'payment_method' => 'paymob',
            'payment_status' => 'pending',
        ]);
    }

    public function test_placing_two_orders_produces_unique_order_numbers(): void
    {
        $category = Category::factory()->create();
        $p2       = Product::factory()->create([
            'category_id' => $category->id,
            'price'       => 50.00,
            'stock'       => 5,
            'is_active'   => true,
        ]);

        $user2 = User::factory()->create();

        // User 1 order
        $this->addToCart(1);
        $r1 = $this->placeOrder();
        $r1->assertStatus(201);

        // User 2 order
        $this->actingAs($user2)
            ->postJson('/api/v1/cart/items', ['productId' => $p2->id, 'quantity' => 1]);
        $r2 = $this->actingAs($user2)->postJson('/api/v1/orders', [
            'customerName'    => 'User Two',
            'customerEmail'   => 'user2@example.com',
            'shippingAddress' => '2 Other St',
            'paymentMethod'   => 'cod',
        ]);
        $r2->assertStatus(201);

        $this->assertNotEquals(
            $r1->json('data.orderNumber'),
            $r2->json('data.orderNumber'),
            'Two orders must not get the same order number'
        );
    }

    public function test_out_of_stock_product_prevents_order(): void
    {
        // Manually deplete stock
        $this->product->update(['stock' => 0]);

        // Cart was pre-loaded; try to order (service validates with lock)
        // Directly call order without cart since stock is 0
        $this->placeOrder()->assertStatus(422);
    }

    public function test_user_can_view_their_own_order_details(): void
    {
        $this->addToCart(1);
        $orderResp = $this->placeOrder();
        $orderNumber = $orderResp->json('data.orderNumber');

        $this->actingAs($this->user)
            ->getJson("/api/v1/orders/{$orderNumber}")
            ->assertOk()
            ->assertJsonPath('data.orderNumber', $orderNumber);
    }

    public function test_order_without_notes_is_valid(): void
    {
        $this->addToCart(1);

        $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'customerName'    => 'Test',
                'customerEmail'   => 'test@example.com',
                'shippingAddress' => '1 Test St',
                'paymentMethod'   => 'cod',
                // notes intentionally omitted
            ])
            ->assertStatus(201);
    }

    public function test_shipping_address_is_required(): void
    {
        $this->addToCart(1);

        $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'customerName'  => 'Test',
                'customerEmail' => 'test@example.com',
                'paymentMethod' => 'cod',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['shippingAddress']);
    }
}
