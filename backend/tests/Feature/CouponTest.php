<?php

namespace Tests\Feature;

use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_validate_valid_coupon(): void
    {
        Coupon::factory()->create([
            'code'             => 'SAVE10',
            'type'             => 'percent',
            'value'            => 10,
            'min_order_amount' => 0,
            'is_active'        => true,
        ]);

        $this->postJson('/api/v1/coupons/validate', [
            'code'       => 'SAVE10',
            'orderTotal' => 200,
        ])
        ->assertOk()
        ->assertJsonPath('data.code', 'SAVE10')
        ->assertJson(['data' => ['discountAmount' => 20.0]]);
    }

    public function test_invalid_coupon_returns_422(): void
    {
        $this->postJson('/api/v1/coupons/validate', [
            'code'       => 'INVALID',
            'orderTotal' => 100,
        ])->assertStatus(422);
    }

    public function test_coupon_below_minimum_order_fails(): void
    {
        Coupon::factory()->create([
            'code'             => 'BIG50',
            'type'             => 'fixed',
            'value'            => 50,
            'min_order_amount' => 500,
            'is_active'        => true,
        ]);

        $this->postJson('/api/v1/coupons/validate', [
            'code'       => 'BIG50',
            'orderTotal' => 100,
        ])->assertStatus(422);
    }
}
