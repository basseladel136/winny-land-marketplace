<?php

namespace App\Services;

use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly CartService   $cartService,
        private readonly CouponService $couponService,
    ) {}

    public function create(User $user, array $data): Order
    {
        $cart = $this->cartService->getOrCreate($user);
        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => ['Your cart is empty.']]);
        }

        // Validate stock availability for all items
        foreach ($cart->items as $item) {
            if ($item->product->stock < $item->quantity) {
                throw ValidationException::withMessages([
                    'stock' => ["{$item->product->name_en} only has {$item->product->stock} items available."],
                ]);
            }
        }

        return DB::transaction(function () use ($user, $data, $cart) {
            $subtotal = $cart->items->sum(fn ($i) => $i->product->price * $i->quantity);

            // Apply coupon
            $discount = 0;
            $coupon   = null;
            if (! empty($data['couponCode'])) {
                $coupon   = $this->couponService->findValid($data['couponCode'], $subtotal);
                $discount = $coupon->calculateDiscount($subtotal);
            }

            $total = max(0, $subtotal - $discount);

            $order = Order::create([
                'user_id'          => $user->id,
                'coupon_id'        => $coupon?->id,
                'order_number'     => Order::generateOrderNumber(),
                'status'           => Order::STATUS_PENDING,
                'subtotal'         => $subtotal,
                'discount_amount'  => $discount,
                'total'            => $total,
                'coupon_code'      => $coupon?->code,
                'payment_method'   => $data['paymentMethod'],
                'customer_name'    => $data['customerName'],
                'customer_email'   => $data['customerEmail'],
                'customer_phone'   => $data['customerPhone'] ?? null,
                'shipping_address' => $data['shippingAddress'],
                'notes'            => $data['notes'] ?? null,
                'locale'           => app()->getLocale(),
            ]);
            // payment_status is guarded against mass-assignment; set directly
            $order->payment_status = Order::PAYMENT_UNPAID;
            $order->save();

            // Create order items & deduct stock
            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id'    => $item->product_id,
                    'product_name'  => $item->product->name_en,
                    'product_image' => $item->product->image,
                    'price'         => $item->product->price,
                    'quantity'      => $item->quantity,
                    'subtotal'      => $item->product->price * $item->quantity,
                ]);

                $item->product->decrement('stock', $item->quantity);
            }

            // Increment coupon uses
            if ($coupon) {
                $coupon->increment('uses_count');
            }

            // Clear cart
            $cart->items()->delete();

            // Queue confirmation email
            SendOrderConfirmationEmail::dispatch($order)->onQueue('emails');

            return $order->load('items');
        });
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);
        return $order->fresh('items');
    }
}
