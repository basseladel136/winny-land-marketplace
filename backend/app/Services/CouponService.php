<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function findValid(string $code, float $orderTotal = 0): Coupon
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (! $coupon || ! $coupon->isValid($orderTotal)) {
            throw ValidationException::withMessages([
                'code' => [__('messages.coupon_invalid')],
            ]);
        }

        return $coupon;
    }

    public function validate(string $code, float $orderTotal): array
    {
        $coupon   = $this->findValid($code, $orderTotal);
        $discount = $coupon->calculateDiscount($orderTotal);

        return [
            'code'           => $coupon->code,
            'type'           => $coupon->type,
            'value'          => (float) $coupon->value,
            'discountAmount' => $discount,
        ];
    }
}
