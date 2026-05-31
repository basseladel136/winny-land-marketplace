<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('name');
            $table->string('role', 20)->default('customer')->after('email_verified_at');
            $table->string('locale', 5)->default('en')->after('role');
            $table->string('avatar', 500)->nullable()->after('locale');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->index(['role'], 'users_role_index');
            $table->index(['is_active'], 'users_is_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
            $table->dropIndex('users_is_active_index');
            $table->dropColumn(['phone', 'role', 'locale', 'avatar', 'is_active']);
        });
    }
};
