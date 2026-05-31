<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
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
            'stock'       => 10,
            'is_active'   => true,
        ]);
    }

    public function test_authenticated_user_can_view_cart(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'items', 'itemCount', 'subtotal']]);
    }

    public function test_guest_cannot_view_cart(): void
    {
        $this->getJson('/api/v1/cart')->assertStatus(401);
    }

    public function test_user_can_add_item_to_cart(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', [
                'productId' => $this->product->id,
                'quantity'  => 2,
            ])
            ->assertOk();

        // items array should have 1 entry (1 distinct product line)
        $this->assertCount(1, $response->json('data.items'));
    }

    public function test_adding_item_exceeding_stock_fails(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', [
                'productId' => $this->product->id,
                'quantity'  => 999,
            ])
            ->assertStatus(422);
    }

    public function test_user_can_remove_item_from_cart(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', ['productId' => $this->product->id]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/cart/items/{$this->product->id}")
            ->assertOk()
            ->assertJsonPath('data.itemCount', 0);
    }

    public function test_user_can_clear_cart(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/cart/items', ['productId' => $this->product->id]);

        $this->actingAs($this->user)
            ->deleteJson('/api/v1/cart')
            ->assertOk();
    }
}
