<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'academico@thomasdebay.com'],
            [
                'username' => 'admin',
                'firstname' => 'Academico',
                'lastname' => 'Admin',
                'password' => Hash::make('secret'),
                'locale' => 'fr',
            ]
        );

        $admin->syncRoles(['admin']);

        $secretary = User::firstOrCreate(
            ['email' => 'secretary@academico.test'],
            [
                'username' => 'secretary',
                'firstname' => 'Main',
                'lastname' => 'Secretary',
                'password' => Hash::make('secret'),
                'locale' => 'fr',
            ]
        );

        $secretary->syncRoles(['secretary']);
    }
}
