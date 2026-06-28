<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    public function summary(): array
    {
        return Cache::remember('analytics:summary', 300, function () {
            $totalRevenue = Order::where('payment_status', 'paid')->sum('total');
            $totalOrders  = Order::count();
            $avgOrder     = $totalOrders ? Order::where('payment_status', 'paid')->avg('total') : 0;

            $revenueByDay = Order::where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw("DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders")
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $topProducts = DB::table('order_items')
                ->select('product_name', DB::raw('SUM(quantity) as sold'), DB::raw('SUM(subtotal) as revenue'))
                ->groupBy('product_name')
                ->orderBy('sold', 'desc')
                ->limit(10)
                ->get();

            $byCategory = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->select('categories.name_en as category', DB::raw('SUM(order_items.subtotal) as revenue'))
                ->groupBy('categories.name_en')
                ->orderBy('revenue', 'desc')
                ->get();

            return [
                'totalRevenue'    => round($totalRevenue, 2),
                'totalOrders'     => $totalOrders,
                'totalProducts'   => Product::count(),
                'totalCustomers'  => User::where('role', 'customer')->count(),
                'avgOrderValue'   => round($avgOrder, 2),
                'revenueByDay'    => $revenueByDay->toArray(),
                'topProducts'     => $topProducts->toArray(),
                'byCategory'      => $byCategory->toArray(),
            ];
        });
    }

    public function customers(int $page = 1): array
    {
        // PERFORMANCE FIX: include the page number in the cache key so
        // different pages are cached independently.
        // CORRECTNESS FIX: pagination URLs in the cached payload used to reference
        // the URL at cache-write time, which broke on subsequent page requests.
        return Cache::remember("analytics:customers:page:{$page}", 300, function () {
            return User::where('role', 'customer')
                ->withCount('orders')
                ->withSum(['orders as total_spent' => fn ($q) => $q->where('payment_status', 'paid')], 'total')
                ->orderBy('total_spent', 'desc')
                ->paginate(20)
                ->toArray();
        });
    }

    /**
     * Flush analytics caches when underlying data changes (e.g. after an order
     * is paid or a new user registers).
     */
    public static function clearCache(): void
    {
        Cache::forget('analytics:summary');
        // Customer pages — flush all known page keys (up to a reasonable limit)
        for ($i = 1; $i <= 50; $i++) {
            Cache::forget("analytics:customers:page:{$i}");
        }
    }
}
