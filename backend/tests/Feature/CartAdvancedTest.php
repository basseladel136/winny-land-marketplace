<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Extended cart tests covering sync, stock edge cases, and inactive-product protection.
 */
class CartAdvancedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $category      = Category::factory()->create();
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'price'       => 25.00,
            'stock'       => 10,
            'is_active'   => true,
        ]);
    }

    public function test_adding_same_item_accumulates_quantity(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', ['productId' => $this->product->id, 'quantity' => 2]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', ['productId' => $this->product->id, 'quantity' => 3]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity'   => 5,
        ]);
    }

    public function test_cannot_exceed_stock_through_accumulation(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', ['productId' => $this->product->id, 'quantity' => 8]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', ['productId' => $this->product->id, 'quantity' => 5])
            ->assertStatus(422);
    }

    public function test_user_can_update_cart_item_quantity(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', ['productId' => $this->product->id, 'quantity' => 2]);

        $this->actingAs($this->user)
            ->patchJson("/api/v1/cart/items/{$this->product->id}", ['quantity' => 7])
            ->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity'   => 7,
        ]);
    }

    public function test_cart_sync_replaces_existing_quantities(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', ['productId' => $this->product->id, 'quantity' => 2]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/sync', [
                'items' => [['productId' => $this->product->id, 'quantity' => 5]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity'   => 5,
        ]);
    }

    public function test_cart_sync_clamps_quantity_to_available_stock(): void
    {
        // stock = 10; send 50 (within max:100 per-item validation but over stock)
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/sync', [
                'items' => [['productId' => $this->product->id, 'quantity' => 50]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity'   => 10, // clamped to available stock
        ]);
    }

    public function test_cart_sync_skips_inactive_products(): void
    {
        $category        = Category::factory()->create();
        $inactiveProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active'   => false,
            'stock'       => 10,
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/sync', [
                'items' => [['productId' => $inactiveProduct->id, 'quantity' => 1]],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('cart_items', ['product_id' => $inactiveProduct->id]);
    }

    public function test_cart_sync_items_array_cannot_exceed_100_entries(): void
    {
        $items = array_fill(0, 101, ['productId' => $this->product->id, 'quantity' => 1]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/sync', ['items' => $items])
            ->assertStatus(422);
    }

    public function test_quantity_per_item_cannot_exceed_100(): void
    {
        $highStock = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'stock'       => 9999,
            'is_active'   => true,
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', [
                'productId' => $highStock->id,
                'quantity'  => 101,
            ])
            ->assertStatus(422);
    }

    public function test_nonexistent_product_cannot_be_added_to_cart(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', ['productId' => 99999, 'quantity' => 1])
            ->assertStatus(422);
    }

    public function test_guest_cannot_add_to_cart(): void
    {
        $this->postJson('/api/v1/cart/items', [
            'productId' => $this->product->id,
            'quantity'  => 1,
        ])->assertStatus(401);
    }
}
