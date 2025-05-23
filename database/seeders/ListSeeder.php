<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the test user
        $user = User::where('email', 'test@example.com')->first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Create sample lists
        $lists = [
            [
                'user_id' => $user->id,
                'name' => 'Grocery Shopping',
                'description' => 'Weekly grocery list for the family',
                'color' => '#34C759', // iOS green
                'icon' => 'cart.fill',
                'is_shared' => false,
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Work Tasks',
                'description' => 'Important work items to complete',
                'color' => '#FF9500', // iOS orange
                'icon' => 'briefcase.fill',
                'is_shared' => true,
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Vacation Planning',
                'description' => 'Things to do and pack for summer vacation',
                'color' => '#007AFF', // iOS blue
                'icon' => 'airplane',
                'is_shared' => false,
                'is_public' => false,
                'sort_order' => 3,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Home Improvement',
                'description' => 'House projects and maintenance tasks',
                'color' => '#8E8E93', // iOS gray
                'icon' => 'hammer.fill',
                'is_shared' => true,
                'is_public' => true,
                'sort_order' => 4,
            ],
            [
                'user_id' => $user->id,
                'name' => 'Books to Read',
                'description' => 'Reading list for this year',
                'color' => '#AF52DE', // iOS purple
                'icon' => 'book.fill',
                'is_shared' => false,
                'is_public' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($lists as $list) {
            DB::table('reusable_lists')->insert($list + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
