<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@gpushare.com')->first();

        if (!$admin) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@gpushare.com',
                'password' => bcrypt('admin123456'),
                'role' => 'admin',
                'status' => 'active',
                'credits' => 10000,
            ]);
            $this->command->info('Admin user created: admin@gpushare.com / admin123456');
        } else {
            $admin->update(['role' => 'admin']);
            $this->command->info('Admin role updated for: ' . $admin->email);
        }
    }
}
