<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Comprehensive admin functionality tests covering all CRUD operations,
 * search/filter, pagination, and validation across every admin resource.
 */
class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    public function test_admin_can_list_users(): void
    {
        User::factory()->count(5)->create(['role' => 'customer']);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        User::factory()->count(3)->create(['role' => 'customer']);
        User::factory()->count(2)->create(['role' => 'admin']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?role=customer');

        $response->assertOk();
        foreach ($response->json('data') as $user) {
            $this->assertEquals('customer', $user['role']);
        }
    }

    public function test_admin_can_search_users(): void
    {
        User::factory()->create(['name' => 'Alice Wonderland', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Bob Builder',      'email' => 'bob@example.com']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?search=alice');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('alice@example.com', $response->json('data.0.email'));
    }

    public function test_admin_can_view_individual_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_admin_can_deactivate_a_customer(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/users/{$user->id}", ['isActive' => false])
            ->assertOk();

        $this->assertFalse((bool) $user->fresh()->is_active);
    }

    public function test_admin_can_delete_a_customer(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/users/{$user->id}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_role_filter_rejects_invalid_role(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?role=superuser')
            ->assertStatus(422);
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public function test_admin_can_create_category(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/categories', [
                'nameEn'    => 'Electronics',
                'nameAr'    => 'إلكترونيات',
                'isActive'  => true,
                'sortOrder' => 1,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.nameEn', 'Electronics');

        $this->assertDatabaseHas('categories', ['name_en' => 'Electronics']);
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::factory()->create(['name_en' => 'Old Name']);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/categories/{$category->id}", [
                'nameEn' => 'New Name',
                'nameAr' => 'اسم جديد',
            ])
            ->assertOk()
            ->assertJsonPath('data.nameEn', 'New Name');
    }

    public function test_admin_can_delete_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/categories/{$category->id}")
            ->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    // ── Products ──────────────────────────────────────────────────────────────

    public function test_admin_can_list_products_including_inactive(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'is_active' => true]);
        Product::factory()->create(['category_id' => $category->id, 'is_active' => false]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/products');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_update_product_stock(): void
    {
        $category = Category::factory()->create();
        $product  = Product::factory()->create(['category_id' => $category->id, 'stock' => 10]);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/products/{$product->slug}", [
                'nameEn'     => $product->name_en,
                'nameAr'     => $product->name_ar,
                'price'      => $product->price,
                'stock'      => 50,
                'categoryId' => $category->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.stock', 50);
    }

    public function test_admin_can_deactivate_product(): void
    {
        $category = Category::factory()->create();
        $product  = Product::factory()->create(['category_id' => $category->id, 'is_active' => true]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/products/{$product->slug}", [
                'isActive' => false,
            ])
            ->assertOk();

        $this->assertFalse((bool) $product->fresh()->is_active);
    }

    public function test_admin_can_delete_product(): void
    {
        $category = Category::factory()->create();
        $product  = Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/products/{$product->slug}")
            ->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_image_must_be_http_or_https(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/products', [
                'nameEn'     => 'Test Product',
                'nameAr'     => 'منتج تجريبي',
                'categoryId' => $category->id,
                'price'      => 10,
                'stock'      => 5,
                'image'      => 'file:///etc/passwd',  // SSRF attempt
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    // ── Orders ────────────────────────────────────────────────────────────────

    public function test_admin_can_list_all_orders(): void
    {
        $user = User::factory()->create();
        Order::create([
            'user_id'          => $user->id,
            'order_number'     => 'WL-ADMIN-00001',
            'status'           => 'pending',
            'subtotal'         => 100,
            'total'            => 100,
            'customer_name'    => 'Test',
            'customer_email'   => 'test@example.com',
            'shipping_address' => '1 Test St',
        ]);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/orders')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_filter_orders_by_status(): void
    {
        $user = User::factory()->create();
        Order::create([
            'user_id'          => $user->id,
            'order_number'     => 'WL-ADMIN-00002',
            'status'           => 'shipped',
            'subtotal'         => 100,
            'total'            => 100,
            'customer_name'    => 'Test',
            'customer_email'   => 'test@example.com',
            'shipping_address' => '1 Test St',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/orders?status=shipped');

        $response->assertOk();
        foreach ($response->json('data') as $order) {
            $this->assertEquals('shipped', $order['status']);
        }
    }

    public function test_admin_can_search_orders(): void
    {
        $user = User::factory()->create();
        Order::create([
            'user_id'          => $user->id,
            'order_number'     => 'WL-ADMIN-00003',
            'status'           => 'pending',
            'subtotal'         => 100,
            'total'            => 100,
            'customer_name'    => 'Alice Smith',
            'customer_email'   => 'alice@example.com',
            'shipping_address' => '1 Test St',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/orders?search=alice');

        $response->assertOk();
        $found = collect($response->json('data'))->first(
            fn ($o) => $o['orderNumber'] === 'WL-ADMIN-00003'
        );
        $this->assertNotNull($found);
    }

    public function test_admin_can_update_order_status(): void
    {
        $user  = User::factory()->create();
        $order = Order::create([
            'user_id'          => $user->id,
            'order_number'     => 'WL-ADMIN-00004',
            'status'           => 'pending',
            'subtotal'         => 100,
            'total'            => 100,
            'customer_name'    => 'Test',
            'customer_email'   => 'test@example.com',
            'shipping_address' => '1 Test St',
        ]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/orders/{$order->order_number}/status", [
                'status' => 'shipped',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'shipped');
    }

    public function test_admin_cannot_set_invalid_order_status(): void
    {
        $user  = User::factory()->create();
        $order = Order::create([
            'user_id'          => $user->id,
            'order_number'     => 'WL-ADMIN-00005',
            'status'           => 'pending',
            'subtotal'         => 100,
            'total'            => 100,
            'customer_name'    => 'Test',
            'customer_email'   => 'test@example.com',
            'shipping_address' => '1 Test St',
        ]);

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/orders/{$order->order_number}/status", [
                'status' => 'hacked',
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_update_payment_status(): void
    {
        $user  = User::factory()->create();
        $order = Order::create([
            'user_id'          => $user->id,
            'order_number'     => 'WL-ADMIN-00006',
            'status'           => 'pending',
            'subtotal'         => 100,
            'total'            => 100,
            'customer_name'    => 'Test',
            'customer_email'   => 'test@example.com',
            'shipping_address' => '1 Test St',
        ]);
        $order->payment_status = 'unpaid';
        $order->save();

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/admin/orders/{$order->order_number}/payment-status", [
                'paymentStatus' => 'paid',
            ])
            ->assertOk()
            ->assertJsonPath('data.paymentStatus', 'paid');
    }

    // ── Coupons ───────────────────────────────────────────────────────────────

    public function test_admin_can_create_coupon(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/coupons', [
                'code'     => 'SUMMER20',
                'type'     => 'percent',
                'value'    => 20,
                'isActive' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.code', 'SUMMER20');

        $this->assertDatabaseHas('coupons', ['code' => 'SUMMER20']);
    }

    public function test_admin_can_deactivate_coupon(): void
    {
        $coupon = Coupon::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/coupons/{$coupon->id}", [
                'isActive' => false,
            ])
            ->assertOk();

        $this->assertFalse((bool) $coupon->fresh()->is_active);
    }

    public function test_admin_can_delete_coupon(): void
    {
        $coupon = Coupon::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/coupons/{$coupon->id}")
            ->assertOk();

        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    public function test_coupon_code_is_stored_uppercase(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/coupons', [
                'code'  => 'lower20',
                'type'  => 'fixed',
                'value' => 10,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('coupons', ['code' => 'LOWER20']);
    }

    // ── Analytics ─────────────────────────────────────────────────────────────

    public function test_admin_can_view_analytics_summary(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/analytics/summary')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['totalRevenue', 'totalOrders', 'totalProducts', 'totalCustomers'],
            ]);
    }

    public function test_admin_can_view_customer_analytics(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/analytics/customers')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
