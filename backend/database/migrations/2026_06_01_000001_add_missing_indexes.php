<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // orders.coupon_id — foreignId creates a FK constraint but NOT a lookup index
        // in PostgreSQL. Querying orders by coupon (admin analytics) was doing a seq scan.
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['coupon_id'], 'orders_coupon_id_index');
        });

        // payment_reference uniqueness check on every webhook call — needs an index
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['payment_reference'], 'orders_payment_reference_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_coupon_id_index');
            $table->dropIndex('orders_payment_reference_index');
        });
    }
};
