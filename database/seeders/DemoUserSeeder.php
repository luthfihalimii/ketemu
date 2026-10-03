<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoUserSeeder extends Seeder
{
    /**
     * Seed non-production demo accounts only. Never runs in production, and
     * never ships a hard-coded default password.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->warn('DemoUserSeeder dilewati pada environment production.');

            return;
        }

        $password = config('ketemupens.demo.password') ?: Str::password(16);

        $accounts = [
            ['name' => 'Admin KETEMU', 'email' => 'admin@ketemupens.test', 'role' => Role::Admin],
            ['name' => 'Satpam Pos D4', 'email' => 'satpam@ketemupens.test', 'role' => Role::Guard],
            ['name' => 'Luthfi Mahasiswa', 'email' => 'mahasiswa@ketemupens.test', 'role' => Role::Student],
            ['name' => 'Andi Pemilik', 'email' => 'andi@ketemupens.test', 'role' => Role::Student],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->firstOrNew(['email' => $account['email']]);
            $user->forceFill([
                'name' => $account['name'],
                'password' => Hash::make($password),
                'role' => $account['role'],
                'email_verified_at' => now(),
            ])->save();
        }

        $this->command->info('Akun demo dibuat dengan password: '.$password);
    }
}
