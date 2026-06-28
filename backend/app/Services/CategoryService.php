<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public function all(bool $adminMode = false): Collection
    {
        $key = $adminMode ? 'categories:all:admin' : 'categories:all:active';

        return Cache::remember($key, 600, function () use ($adminMode) {
            $query = Category::withCount('products')
                ->orderBy('sort_order')
                ->orderBy('name_en');

            if (! $adminMode) {
                $query->where('is_active', true);
            }

            return $query->get();
        });
    }

    public function findBySlug(string $slug): Category
    {
        return Category::withCount('products')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function create(array $data): Category
    {
        $category = Category::create([
            'name_en'    => $data['nameEn'],
            'name_ar'    => $data['nameAr'],
            'is_active'  => $data['isActive'] ?? true,
            'sort_order' => $data['sortOrder'] ?? 0,
        ]);

        $this->clearCache();

        return $category;
    }

    public function update(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);

        $category->update(array_filter([
            'name_en'    => $data['nameEn'] ?? null,
            'name_ar'    => $data['nameAr'] ?? null,
            'is_active'  => $data['isActive'] ?? null,
            'sort_order' => $data['sortOrder'] ?? null,
        ], fn ($v) => ! is_null($v)));

        $this->clearCache();

        return $category->fresh();
    }

    public function delete(int $id): void
    {
        Category::findOrFail($id)->delete();
        $this->clearCache();
    }

    /**
     * Clear only category-related cache entries.
     *
     * SECURITY FIX: Previously used Cache::flush() which would evict ALL cache
     * entries (sessions, Paymob auth tokens, product cache, analytics, etc.),
     * causing unnecessary session disruptions and performance regressions.
     */
    private function clearCache(): void
    {
        Cache::forget('categories:all:admin');
        Cache::forget('categories:all:active');
        // Also bust the products tagged cache since product listings embed category data
        Cache::tags(['products'])->flush();
    }
}
