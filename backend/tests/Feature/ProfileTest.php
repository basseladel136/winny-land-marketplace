<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_fetch_their_profile(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'phone'   => '0100000000',
            'address' => '12 Nile St, Cairo',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.address', '12 Nile St, Cairo')
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'phone', 'address', 'avatar', 'createdAt']]);
    }

    public function test_user_can_update_profile_details(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/v1/auth/me', [
                'name'    => 'Updated Name',
                'phone'   => '0111111111',
                'address' => '5 New Cairo',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.address', '5 New Cairo');

        $this->assertDatabaseHas('users', [
            'id'      => $user->id,
            'name'    => 'Updated Name',
            'address' => '5 New Cairo',
        ]);
    }

    public function test_profile_stats_reflect_user_activity(): void
    {
        /** @var User $user */
        $user    = User::factory()->create();
        $product = Product::factory()->create();

        Order::create([
            'user_id'          => $user->id,
            'order_number'     => 'WL-TEST-00001',
            'status'           => 'delivered',
            'subtotal'         => 100,
            'total'            => 100,
            'customer_name'    => $user->name,
            'customer_email'   => $user->email,
            'shipping_address' => 'addr',
        ]);

        Order::create([
            'user_id'          => $user->id,
            'order_number'     => 'WL-TEST-00002',
            'status'           => 'cancelled',
            'subtotal'         => 50,
            'total'            => 50,
            'customer_name'    => $user->name,
            'customer_email'   => $user->email,
            'shipping_address' => 'addr',
        ]);

        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);
        Review::create(['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 5, 'body' => 'Great']);

        $this->actingAs($user)
            ->getJson('/api/v1/auth/stats')
            ->assertOk()
            ->assertJsonPath('data.ordersCount', 2)
            ->assertJsonPath('data.totalSpent', 100) // cancelled order excluded
            ->assertJsonPath('data.wishlistCount', 1)
            ->assertJsonPath('data.reviewsCount', 1);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $this->actingAs($user)
            ->patchJson('/api/v1/auth/password', [
                'current_password'      => 'old-password',
                'password'              => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }

    public function test_password_change_fails_with_wrong_current_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $this->actingAs($user)
            ->patchJson('/api/v1/auth/password', [
                'current_password'      => 'wrong-password',
                'password'              => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertStatus(422);
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/auth/avatar', [
                'avatar' => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
            ])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $user->refresh();
        $this->assertNotNull($user->avatar);
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $disk->assertExists($user->avatar);
    }

    public function test_avatar_upload_rejects_non_images(): void
    {
        Storage::fake('public');
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/auth/avatar', [
                'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422);
    }
}
