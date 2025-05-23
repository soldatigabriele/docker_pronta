<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemUsageStatsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        
        // Common grocery items with usage statistics
        $groceryStats = [
            ['title' => 'Milk', 'tags' => ['dairy', 'essential'], 'category' => 'Dairy', 'usage_count' => 12, 'completion_count' => 10],
            ['title' => 'Bread', 'tags' => ['bakery', 'essential'], 'category' => 'Bakery', 'usage_count' => 15, 'completion_count' => 14],
            ['title' => 'Eggs', 'tags' => ['protein', 'essential'], 'category' => 'Dairy', 'usage_count' => 8, 'completion_count' => 7],
            ['title' => 'Bananas', 'tags' => ['fruit', 'healthy'], 'category' => 'Produce', 'usage_count' => 6, 'completion_count' => 5],
            ['title' => 'Apples', 'tags' => ['fruit', 'healthy'], 'category' => 'Produce', 'usage_count' => 4, 'completion_count' => 4],
            ['title' => 'Chicken Breast', 'tags' => ['meat', 'protein'], 'category' => 'Meat', 'usage_count' => 7, 'completion_count' => 6],
            ['title' => 'Rice', 'tags' => ['grain', 'staple'], 'category' => 'Pantry', 'usage_count' => 3, 'completion_count' => 3],
            ['title' => 'Pasta', 'tags' => ['grain', 'quick'], 'category' => 'Pantry', 'usage_count' => 5, 'completion_count' => 4],
        ];
        
        // Work-related items
        $workStats = [
            ['title' => 'Team standup meeting', 'tags' => ['meeting', 'daily'], 'category' => 'Meetings', 'usage_count' => 25, 'completion_count' => 23],
            ['title' => 'Code review', 'tags' => ['development', 'review'], 'category' => 'Development', 'usage_count' => 18, 'completion_count' => 16],
            ['title' => 'Update documentation', 'tags' => ['documentation'], 'category' => 'Documentation', 'usage_count' => 8, 'completion_count' => 6],
            ['title' => 'Client call', 'tags' => ['client', 'communication'], 'category' => 'Communication', 'usage_count' => 12, 'completion_count' => 11],
            ['title' => 'Deploy to production', 'tags' => ['deployment', 'urgent'], 'category' => 'Development', 'usage_count' => 6, 'completion_count' => 6],
        ];
        
        // Home improvement items
        $homeStats = [
            ['title' => 'Clean gutters', 'tags' => ['maintenance', 'seasonal'], 'category' => 'Maintenance', 'usage_count' => 4, 'completion_count' => 3],
            ['title' => 'Change air filter', 'tags' => ['maintenance', 'monthly'], 'category' => 'Maintenance', 'usage_count' => 6, 'completion_count' => 5],
            ['title' => 'Mow lawn', 'tags' => ['yard', 'weekly'], 'category' => 'Yard Work', 'usage_count' => 20, 'completion_count' => 18],
            ['title' => 'Fix leaky faucet', 'tags' => ['plumbing', 'repair'], 'category' => 'Plumbing', 'usage_count' => 2, 'completion_count' => 2],
        ];
        
        $allStats = array_merge($groceryStats, $workStats, $homeStats);
        
        foreach ($allStats as $stat) {
            $completionRate = $stat['completion_count'] > 0 ? 
                round(($stat['completion_count'] / $stat['usage_count']) * 100, 2) : 0;
                
            DB::table('item_usage_stats')->insert([
                'user_id' => $user->id,
                'item_title' => $stat['title'],
                'item_title_hash' => hash('sha256', strtolower(trim($stat['title']))),
                'tags' => json_encode($stat['tags']),
                'category' => $stat['category'],
                'usage_count' => $stat['usage_count'],
                'completion_count' => $stat['completion_count'],
                'completion_rate' => $completionRate,
                'first_used_at' => now()->subDays(rand(30, 365)),
                'last_used_at' => now()->subDays(rand(1, 30)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
