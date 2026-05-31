<?php

use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminProductImportController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes  –  v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware(['setLocale', 'throttle:public'])->group(function () {

    // ── Public auth endpoints ──────────────────────────────────────────────

    Route::prefix('auth')->group(function () {
        // Login: strict rate limiting (5/min per IP, brute-force protection)
        Route::post('login',    [AuthController::class, 'login'])
            ->middleware('throttle:login');

        // Register: 5/min per IP
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:register');
    });

    // ── Email verification (GET — clicked from email link) ─────────────────
    // No auth required — the signed URL is the proof of identity.
    // NOTE: We do NOT use ->middleware('signed') here because invalid/expired
    // signatures should redirect to the frontend with an error, not return
    // a JSON 403. The controller handles signature validation manually.
    Route::get('auth/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify');

    // ── Push notification VAPID public key (unauthenticated) ──────────────
    Route::get('notifications/vapid-key', [NotificationController::class, 'vapidKey']);

    // ── Payment webhook (Paymob calls this; no auth header) ───────────────
    Route::post('payments/webhook', [PaymentController::class, 'webhook']);

    // ── Public catalog ────────────────────────────────────────────────────
    Route::get('categories',        [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);

    Route::get('products',        [ProductController::class, 'index']);
    Route::get('products/{slug}', [ProductController::class, 'show']);

    // Product reviews (read-only, public)
    Route::get('products/{productId}/reviews', [ReviewController::class, 'index']);

    // Coupon validation (public so guest checkout works)
    Route::post('coupons/validate', [CouponController::class, 'validate']);

    // ── Authenticated endpoints ────────────────────────────────────────────

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        // ── Auth profile & session management ─────────────────────────────
        Route::prefix('auth')->group(function () {
            Route::post('logout',     [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
            Route::get('me',          [AuthController::class, 'me']);
            Route::patch('me',        [AuthController::class, 'update']);

            // Resend verification email (3 per 5 minutes)
            Route::post('email/resend', [AuthController::class, 'resendVerification'])
                ->middleware('throttle:verification-resend');
        });

        // ── Cart (available even for unverified users so they don't lose items) ──
        Route::prefix('cart')->group(function () {
            Route::get('/',                   [CartController::class, 'show']);
            Route::post('items',              [CartController::class, 'addItem']);
            Route::patch('items/{productId}', [CartController::class, 'updateItem']);
            Route::delete('items/{productId}', [CartController::class, 'removeItem']);
            Route::delete('/',                [CartController::class, 'clear']);
            Route::post('sync',               [CartController::class, 'sync']);
        });

        // ── Verified-only endpoints ────────────────────────────────────────
        // Users must verify their email before placing orders, writing reviews,
        // or managing their wishlist. This prevents fake-email abuse.
        Route::middleware('verified')->group(function () {

            // Wishlist
            Route::prefix('wishlist')->group(function () {
                Route::get('/',                    [WishlistController::class, 'index']);
                Route::post('/{productId}/toggle', [WishlistController::class, 'toggle']);
                Route::delete('/{productId}',      [WishlistController::class, 'remove']);
            });

            // Orders
            Route::prefix('orders')->group(function () {
                Route::get('/',              [OrderController::class, 'index']);
                Route::post('/',             [OrderController::class, 'store']);
                Route::get('/{orderNumber}', [OrderController::class, 'show']);
            });

            // Payment initiation
            Route::post('payments/{orderNumber}/initiate', [PaymentController::class, 'initiate']);

            // Reviews (write)
            Route::post('products/{productId}/reviews',   [ReviewController::class, 'store']);
            Route::patch('products/{productId}/reviews',  [ReviewController::class, 'update']);
            Route::delete('products/{productId}/reviews', [ReviewController::class, 'destroy']);

            // Push notifications
            Route::prefix('notifications')->group(function () {
                Route::post('subscribe',   [NotificationController::class, 'subscribe']);
                Route::post('unsubscribe', [NotificationController::class, 'unsubscribe']);
            });
        });

        // ── Admin endpoints (role-based, email verification required) ──────────
        // The URL prefix is kept as 'admin' for API compatibility.
        // The word "Admin" is never shown in the public-facing UI.
        Route::prefix('admin')->middleware(['verified', 'admin', 'throttle:admin'])->group(function () {

            // Analytics
            Route::get('analytics/summary',   [AdminAnalyticsController::class, 'summary']);
            Route::get('analytics/customers', [AdminAnalyticsController::class, 'customers']);

            // Settings
            Route::get('settings',  [AdminSettingController::class, 'index']);
            Route::post('settings', [AdminSettingController::class, 'upsert']);

            // Categories
            Route::apiResource('categories', AdminCategoryController::class);

            // Products (keyed by slug)
            Route::post('products/import',   [AdminProductImportController::class, 'import']); // must be before {slug}
            Route::get('products',           [AdminProductController::class, 'index']);
            Route::post('products',          [AdminProductController::class, 'store']);
            Route::get('products/{slug}',    [AdminProductController::class, 'show']);
            Route::put('products/{slug}',    [AdminProductController::class, 'update']);
            Route::patch('products/{slug}',  [AdminProductController::class, 'update']);
            Route::delete('products/{slug}', [AdminProductController::class, 'destroy']);

            // Orders
            Route::get('orders',                                [AdminOrderController::class, 'index']);
            Route::get('orders/{orderNumber}',                  [AdminOrderController::class, 'show']);
            Route::patch('orders/{orderNumber}/status',         [AdminOrderController::class, 'updateStatus']);
            Route::patch('orders/{orderNumber}/payment-status', [AdminOrderController::class, 'updatePaymentStatus']);

            // Users
            Route::get('users',         [AdminUserController::class, 'index']);
            Route::get('users/{id}',    [AdminUserController::class, 'show']);
            Route::patch('users/{id}',  [AdminUserController::class, 'update']);
            Route::delete('users/{id}', [AdminUserController::class, 'destroy']);

            // Coupons
            Route::apiResource('coupons', AdminCouponController::class);
        });
    });
});
