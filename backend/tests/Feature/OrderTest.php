<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->user    = User::factory()->create();
        $category      = Category::factory()->create();
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'price'       => 100.00,
            'stock'       => 10,
            'is_active'   => true,
        ]);
    }

    public function test_user_can_place_order(): void
    {
        // Add item to cart first
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', [
                'productId' => $this->product->id,
                'quantity'  => 2,
            ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'customerName'    => 'John Doe',
                'customerEmail'   => 'john@example.com',
                'shippingAddress' => '123 Main St, Cairo',
                'paymentMethod'   => 'cod',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['orderNumber', 'status', 'total', 'items'],
            ]);

        // Stock should decrease
        $this->assertDatabaseHas('products', [
            'id'    => $this->product->id,
            'stock' => 8,
        ]);
    }

    public function test_cannot_place_order_with_empty_cart(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/orders', [
                'customerName'    => 'John Doe',
                'customerEmail'   => 'john@example.com',
                'shippingAddress' => '123 Main St',
                'paymentMethod'   => 'cod',
            ])
            ->assertStatus(422);
    }

    public function test_user_can_view_their_orders(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }
}
