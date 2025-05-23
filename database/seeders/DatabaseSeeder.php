<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the test user first
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Run all seeders in order
        $this->call([
            ListSeeder::class,
            ListItemSeeder::class,
            ListShareSeeder::class,
            ItemUsageStatsSeeder::class,
        ]);
    }
}
