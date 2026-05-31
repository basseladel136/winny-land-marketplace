<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code'             => 'WELCOME10',
                'type'             => 'percent',
                'value'            => 10,
                'min_order_amount' => 100,
                'is_active'        => true,
            ],
            [
                'code'             => 'SAVE50',
                'type'             => 'fixed',
                'value'            => 50,
                'min_order_amount' => 500,
                'is_active'        => true,
            ],
            [
                'code'             => 'FLAT20',
                'type'             => 'percent',
                'value'            => 20,
                'min_order_amount' => 0,
                'max_uses'         => 100,
                'is_active'        => true,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::firstOrCreate(['code' => $coupon['code']], $coupon);
        }

        $this->command->info('Coupons seeded successfully.');
    }
}
