<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function forProduct(int $productId): LengthAwarePaginator
    {
        return Review::with('user')
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function create(User $user, int $productId, array $data): Review
    {
        Product::findOrFail($productId);

        if (Review::where('user_id', $user->id)->where('product_id', $productId)->exists()) {
            throw ValidationException::withMessages([
                'review' => ['You have already reviewed this product.'],
            ]);
        }

        return Review::create([
            'user_id'    => $user->id,
            'product_id' => $productId,
            'rating'     => $data['rating'],
            'body'       => $data['body'] ?? null,
        ])->load('user');
    }

    public function update(User $user, int $productId, array $data): Review
    {
        $review = Review::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->firstOrFail();

        $review->update(array_filter([
            'rating' => $data['rating'] ?? null,
            'body'   => $data['body'] ?? null,
        ], fn ($v) => ! is_null($v)));

        return $review->fresh('user');
    }

    public function delete(User $user, int $productId): void
    {
        Review::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->firstOrFail()
            ->delete();
    }
}
