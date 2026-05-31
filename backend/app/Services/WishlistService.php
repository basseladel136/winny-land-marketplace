<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Collection;

class WishlistService
{
    public function get(User $user): Collection
    {
        return Product::whereHas('wishlists', fn ($q) => $q->where('user_id', $user->id))
            ->with('category')
            ->get();
    }

    public function toggle(User $user, int $productId): bool
    {
        Product::findOrFail($productId);

        $existing = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            return false; // removed
        }

        Wishlist::create(['user_id' => $user->id, 'product_id' => $productId]);
        return true; // added
    }

    public function remove(User $user, int $productId): void
    {
        Wishlist::where('user_id', $user->id)->where('product_id', $productId)->delete();
    }
}
