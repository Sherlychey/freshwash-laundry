<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Cuci & Lipat', 'description' => 'Cuci bersih dan lipat rapi untuk pakaian sehari-hari.', 'unit' => 'kg', 'price' => 7000, 'duration_days' => 3],
            ['name' => 'Cuci & Setrika', 'description' => 'Pakaian bersih, disetrika halus, dan siap dikenakan.', 'unit' => 'kg', 'price' => 10000, 'duration_days' => 3],
            ['name' => 'Express 24 Jam', 'description' => 'Perawatan cepat untuk kebutuhan Anda yang mendesak.', 'unit' => 'kg', 'price' => 15000, 'duration_days' => 1],
        ] as $service) {
            Service::updateOrCreate(['name' => $service['name']], $service + ['is_active' => true]);
        }

        $password = env('FRESHWASH_ADMIN_PASSWORD');
        if (! $password) {
            throw new RuntimeException('Set FRESHWASH_ADMIN_PASSWORD di environment sebelum menjalankan seeder admin.');
        }

        User::updateOrCreate(
            ['email' => env('FRESHWASH_ADMIN_EMAIL', 'admin@freshwash.local')],
            [
                'name' => env('FRESHWASH_ADMIN_NAME', 'FreshWash Admin'),
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
    }
}
