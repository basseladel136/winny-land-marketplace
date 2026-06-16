<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function getOrCreate(User $user): Cart
    {
        return Cart::with(['items.product'])->firstOrCreate(['user_id' => $user->id]);
    }

    public function addItem(User $user, int $productId, int $quantity): Cart
    {
        $product = Product::findOrFail($productId);

        if ($product->stock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ["Only {$product->stock} items available in stock."],
            ]);
        }

        $cart = $this->getOrCreate($user);

        $item = $cart->items()->where('product_id', $productId)->first();

        if ($item) {
            $newQty = $item->quantity + $quantity;
            if ($product->stock < $newQty) {
                throw ValidationException::withMessages([
                    'quantity' => ["Only {$product->stock} items available in stock."],
                ]);
            }
            $item->update(['quantity' => $newQty]);
        } else {
            $cart->items()->create([
                'product_id' => $productId,
                'quantity'   => $quantity,
            ]);
        }

        return $cart->load(['items.product']);
    }

    public function updateItem(User $user, int $productId, int $quantity): Cart
    {
        $product = Product::findOrFail($productId);

        if ($product->stock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ["Only {$product->stock} items available in stock."],
            ]);
        }

        $cart = $this->getOrCreate($user);
        $cart->items()->where('product_id', $productId)->update(['quantity' => $quantity]);

        return $cart->load(['items.product']);
    }

    public function removeItem(User $user, int $productId): Cart
    {
        $cart = $this->getOrCreate($user);
        $cart->items()->where('product_id', $productId)->delete();

        return $cart->load(['items.product']);
    }

    public function clear(User $user): void
    {
        $cart = $this->getOrCreate($user);
        $cart->items()->delete();
    }

    public function sync(User $user, array $items): Cart
    {
        $cart = $this->getOrCreate($user);

        foreach ($items as $item) {
            $productId = $item['productId'];
            $qty       = (int) ($item['quantity'] ?? 1);
            $product   = Product::find($productId);

            if (! $product || $qty <= 0) {
                continue;
            }

            // Clamp to available stock
            $qty = min($qty, $product->stock);

            $existingItem = $cart->items()->where('product_id', $productId)->first();
            if ($existingItem) {
                // Sync replaces quantity — takes the client's value, clamped to stock
                $existingItem->update(['quantity' => $qty]);
            } else {
                $cart->items()->create(['product_id' => $productId, 'quantity' => $qty]);
            }
        }

        return $cart->load(['items.product']);
    }
}
