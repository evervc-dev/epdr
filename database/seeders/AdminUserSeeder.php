<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate([
            'email' => 'elroble.ferreteria.sv@gmail.com',
        ], [
            'name' => 'Administrador',
            'password' => bcrypt('1234'),
        ]);

        $admin->assignRole('admin');
    }
}
