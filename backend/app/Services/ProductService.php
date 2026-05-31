<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    private const CACHE_TTL = 300; // 5 minutes

    public function list(array $filters = [], bool $adminMode = false): LengthAwarePaginator
    {
        // Don't cache in admin mode (needs fresh data)
        if ($adminMode) {
            return $this->buildQuery($filters, $adminMode)->paginate(
                min((int) ($filters['perPage'] ?? 24), 100)
            );
        }

        $cacheKey = 'products:list:' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters, $adminMode) {
            return $this->buildQuery($filters, $adminMode)->paginate(
                min((int) ($filters['perPage'] ?? 24), 100)
            );
        });
    }

    private function buildQuery(array $filters, bool $adminMode = false)
    {
        $query = Product::with('category')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        if (! $adminMode) {
            $query->active();
        } elseif (isset($filters['active'])) {
            $query->where('is_active', (bool) $filters['active']);
        }

        if (! empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $q      = $filters['search'];
            $isPg   = in_array(config('database.default'), ['pgsql', 'postgres']);
            $op     = $isPg ? 'ILIKE' : 'LIKE';
            $query->where(function ($q2) use ($q, $op) {
                $q2->where('name_en', $op, "%{$q}%")
                   ->orWhere('name_ar', $op, "%{$q}%")
                   ->orWhere('description_en', $op, "%{$q}%");
            });
        }

        if (! empty($filters['inStock'])) {
            $query->where('stock', '>', 0);
        }

        if (! empty($filters['featured'])) {
            $query->featured();
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'featured'   => $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc'),
            default      => $query->orderBy('created_at', 'desc'),
        };

        return $query;
    }

    public function find(string $slug, bool $adminMode = false): Product
    {
        $cacheKey = "product:{$slug}";

        if ($adminMode) {
            return Product::with('category')
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->where('slug', $slug)
                ->firstOrFail();
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($slug) {
            return Product::with('category')
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->active()
                ->where('slug', $slug)
                ->firstOrFail();
        });
    }

    public function create(array $data): Product
    {
        $product = Product::create($this->mapData($data));
        $this->clearCache();
        return $product->load('category');
    }

    public function update(string $slug, array $data): Product
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $product->update($this->mapData($data));
        $this->clearCache($slug);
        return $product->fresh('category');
    }

    public function delete(string $slug): void
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $this->clearCache($slug);
        $product->delete();
    }

    private function mapData(array $data): array
    {
        return array_filter([
            'category_id'    => $data['categoryId'] ?? null,
            'name_en'        => $data['nameEn'] ?? null,
            'name_ar'        => $data['nameAr'] ?? null,
            'description_en' => $data['descriptionEn'] ?? null,
            'description_ar' => $data['descriptionAr'] ?? null,
            'price'          => $data['price'] ?? null,
            'compare_price'  => $data['comparePrice'] ?? null,
            'stock'          => $data['stock'] ?? null,
            'sku'            => $data['sku'] ?? null,
            'image'          => $data['image'] ?? null,
            'is_active'      => $data['isActive'] ?? null,
            'is_featured'    => $data['isFeatured'] ?? null,
        ], fn ($v) => ! is_null($v));
    }

    public function clearCache(?string $slug = null): void
    {
        if ($slug) {
            Cache::forget("product:{$slug}");
        }
        // Flush list caches
        Cache::flush();
    }
}
