<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = config('app.admin_email');

        if (! $adminEmail) {
            $this->command->warn('ADMIN_EMAIL is not set. Skipping admin user seeder.');
            return;
        }

        $existing = User::where('email', $adminEmail)->first();

        if ($existing) {
            // Existing user: just sync role and mark email as verified
            $existing->newQuery()->where('id', $existing->id)->update([
                'role'              => 'admin',
                'email_verified_at' => $existing->email_verified_at ?? now(),
                'is_active'         => true,
            ]);
            $this->command->info("Admin user already exists — role synced: {$adminEmail}");
            return;
        }

        // New user: generate a random strong temporary password
        $tempPassword = Str::password(24);  // requires Laravel 10+

        // Insert directly to bypass $fillable restrictions on role
        $user = new User();
        $user->name              = 'Store Manager';
        $user->email             = $adminEmail;
        $user->password          = Hash::make($tempPassword);
        $user->role              = 'admin';
        $user->is_active         = true;
        $user->email_verified_at = now(); // Admin email pre-verified
        $user->locale            = 'en';
        $user->save();

        $this->command->info("Admin user created: {$adminEmail}");
        $this->command->warn("Temporary password: {$tempPassword}");
        $this->command->warn("Change this password immediately — use the reset password flow.");
    }
}
