<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
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
            'is_active'   => true,
        ]);
    }

    public function test_guest_cannot_access_wishlist(): void
    {
        $this->getJson('/api/v1/wishlist')->assertStatus(401);
    }

    public function test_unverified_user_cannot_access_wishlist(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/wishlist')
            ->assertStatus(403);
    }

    public function test_verified_user_can_view_empty_wishlist(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/wishlist')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_user_can_toggle_product_into_wishlist(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/wishlist/{$this->product->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('added', true);

        $this->assertDatabaseHas('wishlists', [
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_user_can_toggle_product_out_of_wishlist(): void
    {
        // Add to wishlist first
        $this->actingAs($this->user)
            ->postJson("/api/v1/wishlist/{$this->product->id}/toggle");

        // Remove via second toggle
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/wishlist/{$this->product->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('added', false);

        $this->assertDatabaseMissing('wishlists', [
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_wishlist_shows_toggled_products(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/wishlist/{$this->product->id}/toggle");

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/wishlist');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->product->id, $response->json('data.0.id'));
    }

    public function test_user_can_explicitly_remove_from_wishlist(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/wishlist/{$this->product->id}/toggle");

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/wishlist/{$this->product->id}")
            ->assertOk();

        $this->assertDatabaseMissing('wishlists', [
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_toggling_nonexistent_product_returns_404(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/wishlist/99999/toggle')
            ->assertStatus(404);
    }

    // ── Profile stats integration ─────────────────────────────────────────────

    public function test_wishlist_count_in_profile_stats_is_accurate(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/wishlist/{$this->product->id}/toggle");

        $this->actingAs($this->user)
            ->getJson('/api/v1/auth/stats')
            ->assertOk()
            ->assertJsonPath('data.wishlistCount', 1);
    }

    public function test_empty_wishlist_shows_zero_count_in_stats(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/auth/stats')
            ->assertOk()
            ->assertJsonPath('data.wishlistCount', 0);
    }

    public function test_wishlist_count_decrements_after_removal(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/wishlist/{$this->product->id}/toggle");

        // Remove via toggle
        $this->actingAs($this->user)
            ->postJson("/api/v1/wishlist/{$this->product->id}/toggle");

        $this->actingAs($this->user)
            ->getJson('/api/v1/auth/stats')
            ->assertOk()
            ->assertJsonPath('data.wishlistCount', 0);
    }

    public function test_wishlist_count_is_isolated_per_user(): void
    {
        $userB = User::factory()->create();

        $this->actingAs($this->user)
            ->postJson("/api/v1/wishlist/{$this->product->id}/toggle");

        // User B's count must still be 0
        $this->actingAs($userB)
            ->getJson('/api/v1/auth/stats')
            ->assertOk()
            ->assertJsonPath('data.wishlistCount', 0);
    }

    public function test_multiple_products_counted_in_wishlist_stats(): void
    {
        $category = Category::factory()->create();
        $p2 = Product::factory()->create(['category_id' => $category->id, 'is_active' => true]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/wishlist/{$this->product->id}/toggle");
        $this->actingAs($this->user)
            ->postJson("/api/v1/wishlist/{$p2->id}/toggle");

        $this->actingAs($this->user)
            ->getJson('/api/v1/auth/stats')
            ->assertOk()
            ->assertJsonPath('data.wishlistCount', 2);
    }
}
