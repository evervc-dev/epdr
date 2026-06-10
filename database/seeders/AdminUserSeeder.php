<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate([
            'email' => 'admin@cepja.edu.sv',
        ], [
            'name' => 'Administrador',
            'password' => Hash::make('Admin@2025!'),
        ]);

        $admin->assignRole('admin');
    }
}
