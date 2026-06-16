<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hashed 6-digit verification code (never stored in plaintext).
            if (! Schema::hasColumn('users', 'email_otp')) {
                $table->string('email_otp')->nullable()->after('email_verified_at');
            }
            if (! Schema::hasColumn('users', 'email_otp_expires_at')) {
                $table->timestamp('email_otp_expires_at')->nullable()->after('email_otp');
            }
            if (! Schema::hasColumn('users', 'email_otp_attempts')) {
                $table->unsignedTinyInteger('email_otp_attempts')->default(0)->after('email_otp_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['email_otp', 'email_otp_expires_at', 'email_otp_attempts'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
