<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
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

    public function test_guest_can_read_reviews(): void
    {
        $this->getJson("/api/v1/products/{$this->product->id}/reviews")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_guest_cannot_create_review(): void
    {
        $this->postJson("/api/v1/products/{$this->product->id}/reviews", [
            'rating' => 5,
            'body'   => 'Great product!',
        ])->assertStatus(401);
    }

    public function test_unverified_user_cannot_create_review(): void
    {
        /** @var User $user */
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/products/{$this->product->id}/reviews", [
                'rating' => 4,
            ])
            ->assertStatus(403);
    }

    public function test_verified_user_can_create_review(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/products/{$this->product->id}/reviews", [
                'rating' => 5,
                'body'   => 'Excellent product!',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.rating', 5);

        $this->assertDatabaseHas('reviews', [
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'rating'     => 5,
        ]);
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/products/{$this->product->id}/reviews", [
                'rating' => 6,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);

        $this->actingAs($this->user)
            ->postJson("/api/v1/products/{$this->product->id}/reviews", [
                'rating' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_user_cannot_review_same_product_twice(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/products/{$this->product->id}/reviews", ['rating' => 5]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/products/{$this->product->id}/reviews", ['rating' => 3])
            ->assertStatus(422);
    }

    public function test_user_can_update_their_review(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/products/{$this->product->id}/reviews", [
                'rating' => 5,
                'body'   => 'Original text',
            ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/products/{$this->product->id}/reviews", [
                'rating' => 3,
                'body'   => 'Changed my mind',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.rating', 3);

        $this->assertDatabaseHas('reviews', [
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'rating'     => 3,
        ]);
    }

    public function test_user_can_delete_their_review(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/products/{$this->product->id}/reviews", ['rating' => 4]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/products/{$this->product->id}/reviews")
            ->assertOk();

        $this->assertDatabaseMissing('reviews', [
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_different_users_can_review_same_product(): void
    {
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $this->actingAs($this->user)
            ->postJson("/api/v1/products/{$this->product->id}/reviews", ['rating' => 5])
            ->assertStatus(201);

        $this->actingAs($otherUser)
            ->postJson("/api/v1/products/{$this->product->id}/reviews", ['rating' => 3])
            ->assertStatus(201);

        $this->assertDatabaseCount('reviews', 2);
    }
}
