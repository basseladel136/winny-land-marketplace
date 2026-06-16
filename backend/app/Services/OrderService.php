<?php

namespace App\Services;

use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
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

        return DB::transaction(function () use ($user, $data, $cart) {
            // Lock all product rows for update to prevent concurrent overselling
            $productIds = $cart->items->pluck('product_id')->all();
            $products   = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            // Re-validate stock with locked rows (concurrent requests may have changed it)
            foreach ($cart->items as $item) {
                $product = $products->get($item->product_id);
                if (! $product || $product->stock < $item->quantity) {
                    $name  = $product?->name_en ?? 'A product';
                    $avail = $product?->stock ?? 0;
                    throw ValidationException::withMessages([
                        'stock' => ["{$name} only has {$avail} items available."],
                    ]);
                }
            }

            $subtotal = $cart->items->sum(fn ($i) => $products->get($i->product_id)->price * $i->quantity);

            // Apply coupon with lock to prevent concurrent over-use
            $discount = 0;
            $coupon   = null;
            if (! empty($data['couponCode'])) {
                $coupon = Coupon::where('code', strtoupper($data['couponCode']))
                    ->lockForUpdate()
                    ->first();

                if (! $coupon || ! $coupon->isValid($subtotal)) {
                    throw ValidationException::withMessages([
                        'couponCode' => [__('messages.coupon_invalid')],
                    ]);
                }
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
            // payment_status is guarded against mass-assignment; set directly.
            // Online methods await a gateway charge (PENDING enables /initiate);
            // cash on delivery is collected later, so it starts UNPAID.
            $order->payment_status = $data['paymentMethod'] === 'paymob'
                ? Order::PAYMENT_PENDING
                : Order::PAYMENT_UNPAID;
            $order->save();

            // Create order items & deduct stock using the locked product records
            foreach ($cart->items as $item) {
                $product = $products->get($item->product_id);

                $order->items()->create([
                    'product_id'    => $item->product_id,
                    'product_name'  => $product->name_en,
                    'product_image' => $product->image,
                    'price'         => $product->price,
                    'quantity'      => $item->quantity,
                    'subtotal'      => $product->price * $item->quantity,
                ]);

                $product->decrement('stock', $item->quantity);
            }

            // Increment coupon uses inside the same transaction
            if ($coupon) {
                $coupon->increment('uses_count');
            }

            // Clear cart
            $cart->items()->delete();

            // Queue confirmation email (dispatched after commit via after_commit config)
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
