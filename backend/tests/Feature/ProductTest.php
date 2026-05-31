<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(5)->create(['category_id' => $category->id, 'is_active' => true]);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonStructure([
                'data'  => [['id', 'name', 'price', 'slug']],
                'meta'  => ['total'],
                'links' => [],
            ]);
    }

    public function test_can_get_single_product(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $this->getJson("/api/v1/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', $product->slug);
    }

    public function test_inactive_product_not_visible_publicly(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $this->getJson("/api/v1/products/{$product->slug}")->assertNotFound();
    }

    public function test_admin_can_create_product(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/products', [
                'nameEn'     => 'Test Product',
                'nameAr'     => 'منتج تجريبي',
                'categoryId' => $category->id,
                'price'      => 99.99,
                'stock'      => 50,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.nameEn', 'Test Product');
    }

    public function test_non_admin_cannot_create_product(): void
    {
        $user     = User::factory()->create(['role' => 'customer']);
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/admin/products', [
                'nameEn'     => 'Test',
                'nameAr'     => 'تجربة',
                'categoryId' => $category->id,
                'price'      => 10,
                'stock'      => 5,
            ])
            ->assertStatus(403);
    }

    public function test_can_search_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['name_en' => 'Red Shoes', 'category_id' => $category->id, 'is_active' => true]);
        Product::factory()->create(['name_en' => 'Blue Hat',  'category_id' => $category->id, 'is_active' => true]);

        $this->getJson('/api/v1/products?search=shoes')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
