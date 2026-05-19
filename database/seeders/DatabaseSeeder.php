<?php

namespace Database\Seeders;

use App\Models\Period;
use App\Models\User;
use App\Models\Year;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ReferenceDataSeeder::class,
            PermissionsSeeder::class,
            TestSeeder::class,
        ]);

        $admin = User::factory()->create([
            'email' => 'academico@thomasdebay.com',
            'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
        ]);

        $admin->assignRole('admin');

        $year = Year::create(['name' => now()->year]);

        Period::create([
            'name' => 'Default',
            'year_id' => $year->id,
            'start' => now()->subDays(rand(1, 30))->format('Y-m-d'),
            'end' => now()->addDays(rand(10, 90))->format('Y-m-d'),
            'order' => 1,
        ]);
    }
}
