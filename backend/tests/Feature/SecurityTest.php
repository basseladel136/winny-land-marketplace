<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Security regression suite covering:
 *  - Broken Access Control / IDOR
 *  - Vertical privilege escalation (customer → admin)
 *  - Horizontal privilege escalation (user A accessing user B's data)
 *  - Sensitive field mass-assignment protection
 *  - Unauthenticated access to protected resources
 *  - Unverified user access to verified-only resources
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeOrder(User $user, string $orderNumber = 'WL-TEST-00001'): Order
    {
        Queue::fake();
        $order = Order::create([
            'user_id'          => $user->id,
            'order_number'     => $orderNumber,
            'status'           => Order::STATUS_PENDING,
            'subtotal'         => 100.00,
            'discount_amount'  => 0,
            'total'            => 100.00,
            'payment_method'   => 'cod',
            'customer_name'    => $user->name,
            'customer_email'   => $user->email,
            'shipping_address' => '1 Test St',
        ]);
        $order->payment_status = Order::PAYMENT_UNPAID;
        $order->save();
        return $order;
    }

    // ── Unauthenticated access ────────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_any_protected_endpoint(): void
    {
        $endpoints = [
            ['GET',    '/api/v1/auth/me'],
            ['POST',   '/api/v1/auth/logout'],
            ['GET',    '/api/v1/cart'],
            ['GET',    '/api/v1/orders'],
            ['GET',    '/api/v1/wishlist'],
            ['GET',    '/api/v1/admin/users'],
            ['GET',    '/api/v1/admin/orders'],
            ['GET',    '/api/v1/admin/analytics/summary'],
        ];

        foreach ($endpoints as [$method, $path]) {
            $response = $this->json($method, $path);
            $this->assertContains(
                $response->getStatusCode(),
                [401, 403],
                "Expected 401/403 for {$method} {$path}, got {$response->getStatusCode()}"
            );
        }
    }

    // ── Unverified user restrictions ──────────────────────────────────────────

    public function test_unverified_user_cannot_place_order(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'customerName'    => 'Test',
                'customerEmail'   => 'test@example.com',
                'shippingAddress' => '1 Test St',
                'paymentMethod'   => 'cod',
            ])
            ->assertStatus(403);
    }

    public function test_unverified_user_cannot_access_wishlist(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->getJson('/api/v1/wishlist')->assertStatus(403);
    }

    public function test_unverified_user_cannot_write_review(): void
    {
        $user    = User::factory()->unverified()->create();
        $product = Product::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->postJson("/api/v1/products/{$product->id}/reviews", ['rating' => 5])
            ->assertStatus(403);
    }

    // ── IDOR — Order access ───────────────────────────────────────────────────

    public function test_user_cannot_read_another_users_order(): void
    {
        $userA = $this->customer();
        $userB = $this->customer();
        $order = $this->makeOrder($userB, 'WL-TEST-00002');

        $this->actingAs($userA)
            ->getJson("/api/v1/orders/{$order->order_number}")
            ->assertStatus(404); // appears as "not found" to prevent enumeration
    }

    public function test_user_cannot_initiate_payment_for_another_users_order(): void
    {
        $userA = $this->customer();
        $userB = $this->customer();
        $order = $this->makeOrder($userB, 'WL-TEST-00003');

        $this->actingAs($userA)
            ->postJson("/api/v1/payments/{$order->order_number}/initiate")
            ->assertStatus(404);
    }

    // ── Vertical privilege escalation: customer → admin ───────────────────────

    public function test_customer_cannot_access_admin_user_list(): void
    {
        $this->actingAs($this->customer())
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403);
    }

    public function test_customer_cannot_create_product_via_admin(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->customer())
            ->postJson('/api/v1/admin/products', [
                'nameEn'     => 'Hacked Product',
                'nameAr'     => 'منتج',
                'categoryId' => $category->id,
                'price'      => 9.99,
                'stock'      => 100,
            ])
            ->assertStatus(403);
    }

    public function test_customer_cannot_delete_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->customer())
            ->deleteJson("/api/v1/admin/products/{$product->slug}")
            ->assertStatus(403);
    }

    public function test_customer_cannot_update_order_status(): void
    {
        $userA = $this->customer();
        $order = $this->makeOrder($userA, 'WL-TEST-00004');

        $this->actingAs($userA)
            ->patchJson("/api/v1/admin/orders/{$order->order_number}/status", [
                'status' => 'delivered',
            ])
            ->assertStatus(403);
    }

    public function test_customer_cannot_access_analytics(): void
    {
        $this->actingAs($this->customer())
            ->getJson('/api/v1/admin/analytics/summary')
            ->assertStatus(403);
    }

    public function test_customer_cannot_manage_coupons(): void
    {
        $this->actingAs($this->customer())
            ->postJson('/api/v1/admin/coupons', [
                'code'  => 'FREE100',
                'type'  => 'percent',
                'value' => 100,
            ])
            ->assertStatus(403);
    }

    public function test_customer_cannot_manage_settings(): void
    {
        $this->actingAs($this->customer())
            ->postJson('/api/v1/admin/settings', [
                'settings' => ['site_name' => 'Hacked'],
            ])
            ->assertStatus(403);
    }

    // ── Mass-assignment protection ────────────────────────────────────────────

    public function test_user_cannot_escalate_their_own_role_via_profile_update(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->patchJson('/api/v1/auth/me', [
                'name' => 'Normal Update',
                'role' => 'admin',       // must be ignored
            ]);

        $this->assertEquals('customer', $user->fresh()->role);
    }

    public function test_user_cannot_set_is_admin_flag_via_profile_update(): void
    {
        $user = $this->customer();

        $this->actingAs($user)
            ->patchJson('/api/v1/auth/me', [
                'name'     => 'Normal',
                'is_admin' => true,
            ]);

        // The model has no is_admin column, but verify role didn't change
        $this->assertEquals('customer', $user->fresh()->role);
    }

    public function test_admin_cannot_change_user_role_via_admin_api(): void
    {
        $admin  = $this->admin();
        $target = $this->customer();

        // The admin user update endpoint only allows toggling is_active
        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/users/{$target->id}", [
                'role'     => 'admin',
                'isActive' => false,
            ]);

        // Role must remain 'customer'
        $this->assertEquals('customer', $target->fresh()->role);
    }

    public function test_payment_status_cannot_be_mass_assigned_via_order_placement(): void
    {
        Queue::fake();
        $user     = $this->customer();
        $category = Category::factory()->create();
        $product  = Product::factory()->create([
            'category_id' => $category->id,
            'price'       => 50.00,
            'stock'       => 10,
            'is_active'   => true,
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/cart/items', ['productId' => $product->id, 'quantity' => 1]);

        $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'customerName'    => 'Test',
                'customerEmail'   => 'test@example.com',
                'shippingAddress' => '1 Test St',
                'paymentMethod'   => 'cod',
                'paymentStatus'   => 'paid',   // attempt to forge paid status
                'payment_status'  => 'paid',   // snake_case variant
            ]);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        // Must start as unpaid (cod) not paid
        $this->assertEquals(Order::PAYMENT_UNPAID, $order->payment_status);
    }

    // ── IDOR — Review ownership ───────────────────────────────────────────────

    public function test_user_cannot_delete_another_users_review(): void
    {
        $userA   = $this->customer();
        $userB   = $this->customer();
        $product = Product::factory()->create(['is_active' => true]);

        Review::create([
            'user_id'    => $userB->id,
            'product_id' => $product->id,
            'rating'     => 5,
        ]);

        // userA tries to delete userB's review on the same product
        $this->actingAs($userA)
            ->deleteJson("/api/v1/products/{$product->id}/reviews")
            ->assertStatus(404);
    }

    // ── Admin protection of admin account ─────────────────────────────────────

    public function test_admin_cannot_delete_the_primary_admin_account(): void
    {
        $adminEmail = 'primary@admin.com';
        config(['app.admin_email' => $adminEmail]);

        $admin = $this->admin();
        $admin->email = $adminEmail;
        $admin->save();

        // Even another admin (in a multi-admin future) cannot delete the primary
        $anotherAdmin = $this->admin();

        $this->actingAs($anotherAdmin)
            ->deleteJson("/api/v1/admin/users/{$admin->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_deactivate_the_primary_admin_account(): void
    {
        $adminEmail = 'primary@admin.com';
        config(['app.admin_email' => $adminEmail]);

        $admin        = $this->admin();
        $admin->email = $adminEmail;
        $admin->save();

        $anotherAdmin = $this->admin();

        $this->actingAs($anotherAdmin)
            ->patchJson("/api/v1/admin/users/{$admin->id}", ['isActive' => false])
            ->assertStatus(422);

        $this->assertTrue((bool) $admin->fresh()->is_active);
    }

    // ── Settings key injection ────────────────────────────────────────────────

    public function test_admin_cannot_save_unknown_setting_keys(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/settings', [
                'settings' => [
                    'site_name'    => 'Valid',
                    'injected_key' => 'malicious value',
                ],
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Unknown setting key(s): injected_key']);
    }

    public function test_admin_can_save_known_setting_keys(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/settings', [
                'settings' => [
                    'site_name'    => 'Winny Land',
                    'support_email'=> 'support@winny.com',
                ],
            ])
            ->assertOk();
    }
}
