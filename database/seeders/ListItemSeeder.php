<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();
        $lists = DB::table('reusable_lists')->where('user_id', $user->id)->get();
        
        // Grocery Shopping items
        $groceryList = $lists->where('name', 'Grocery Shopping')->first();
        if ($groceryList) {
            $groceryItems = [
                ['title' => 'Milk', 'tags' => ['dairy', 'essential'], 'category' => 'Dairy', 'is_completed' => true, 'usage_count' => 5],
                ['title' => 'Bread', 'tags' => ['bakery', 'essential'], 'category' => 'Bakery', 'is_completed' => true, 'usage_count' => 8],
                ['title' => 'Bananas', 'tags' => ['fruit', 'healthy'], 'category' => 'Produce', 'is_completed' => false, 'usage_count' => 3],
                ['title' => 'Greek Yogurt', 'tags' => ['dairy', 'protein'], 'category' => 'Dairy', 'is_completed' => false, 'usage_count' => 2],
                ['title' => 'Chicken Breast', 'tags' => ['meat', 'protein'], 'category' => 'Meat', 'is_completed' => false, 'usage_count' => 4],
                ['title' => 'Spinach', 'tags' => ['vegetable', 'healthy'], 'category' => 'Produce', 'is_completed' => true, 'usage_count' => 3],
                ['title' => 'Olive Oil', 'tags' => ['cooking', 'essential'], 'category' => 'Pantry', 'is_completed' => false, 'usage_count' => 1],
            ];
            
            foreach ($groceryItems as $index => $item) {
                DB::table('list_items')->insert([
                    'reusable_list_id' => $groceryList->id,
                    'created_by_user_id' => $user->id,
                    'title' => $item['title'],
                    'is_completed' => $item['is_completed'],
                    'completed_at' => $item['is_completed'] ? now()->subHours(rand(1, 24)) : null,
                    'completed_by_user_id' => $item['is_completed'] ? $user->id : null,
                    'usage_count' => $item['usage_count'],
                    'last_used_at' => now()->subDays(rand(1, 7)),
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Work Tasks items
        $workList = $lists->where('name', 'Work Tasks')->first();
        if ($workList) {
            $workItems = [
                ['title' => 'Finish quarterly report', 'description' => 'Complete Q4 financial analysis', 'tags' => ['urgent', 'finance'], 'category' => 'Reports', 'is_completed' => false, 'usage_count' => 1],
                ['title' => 'Team standup meeting', 'description' => 'Daily sync with development team', 'tags' => ['meeting', 'daily'], 'category' => 'Meetings', 'is_completed' => true, 'usage_count' => 15],
                ['title' => 'Code review for new feature', 'description' => 'Review pull request #123', 'tags' => ['development', 'review'], 'category' => 'Development', 'is_completed' => true, 'usage_count' => 8],
                ['title' => 'Update project documentation', 'description' => 'Add API endpoints documentation', 'tags' => ['documentation', 'api'], 'category' => 'Documentation', 'is_completed' => false, 'usage_count' => 2],
                ['title' => 'Schedule client call', 'description' => 'Follow up on project requirements', 'tags' => ['client', 'communication'], 'category' => 'Communication', 'is_completed' => false, 'usage_count' => 3],
            ];
            
            foreach ($workItems as $index => $item) {
                DB::table('list_items')->insert([
                    'reusable_list_id' => $workList->id,
                    'created_by_user_id' => $user->id,
                    'title' => $item['title'],
                    'is_completed' => $item['is_completed'],
                    'completed_at' => $item['is_completed'] ? now()->subHours(rand(1, 48)) : null,
                    'completed_by_user_id' => $item['is_completed'] ? $user->id : null,
                    'usage_count' => $item['usage_count'],
                    'last_used_at' => now()->subDays(rand(1, 14)),
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Vacation Planning items
        $vacationList = $lists->where('name', 'Vacation Planning')->first();
        if ($vacationList) {
            $vacationItems = [
                ['title' => 'Book flights', 'description' => 'Find tickets to Hawaii', 'tags' => ['travel', 'urgent'], 'category' => 'Transportation', 'is_completed' => true, 'usage_count' => 1],
                ['title' => 'Reserve hotel', 'description' => 'Beach resort for 7 nights', 'tags' => ['accommodation', 'beach'], 'category' => 'Accommodation', 'is_completed' => true, 'usage_count' => 1],
                ['title' => 'Pack sunscreen', 'description' => 'SPF 30 or higher', 'tags' => ['packing', 'essentials'], 'category' => 'Packing', 'is_completed' => false, 'usage_count' => 3],
                ['title' => 'Download offline maps', 'description' => 'Google Maps for the island', 'tags' => ['technology', 'navigation'], 'category' => 'Preparation', 'is_completed' => false, 'usage_count' => 2],
                ['title' => 'Notify bank of travel', 'description' => 'Avoid card blocks abroad', 'tags' => ['finance', 'important'], 'category' => 'Preparation', 'is_completed' => false, 'usage_count' => 1],
            ];
            
            foreach ($vacationItems as $index => $item) {
                DB::table('list_items')->insert([
                    'reusable_list_id' => $vacationList->id,
                    'created_by_user_id' => $user->id,
                    'title' => $item['title'],
                    'is_completed' => $item['is_completed'],
                    'completed_at' => $item['is_completed'] ? now()->subDays(rand(1, 30)) : null,
                    'completed_by_user_id' => $item['is_completed'] ? $user->id : null,
                    'usage_count' => $item['usage_count'],
                    'last_used_at' => now()->subDays(rand(1, 21)),
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Home Improvement items
        $homeList = $lists->where('name', 'Home Improvement')->first();
        if ($homeList) {
            $homeItems = [
                ['title' => 'Fix leaky faucet', 'description' => 'Kitchen sink dripping', 'tags' => ['plumbing', 'urgent'], 'category' => 'Plumbing', 'is_completed' => true, 'usage_count' => 2],
                ['title' => 'Paint living room', 'description' => 'Light gray color scheme', 'tags' => ['painting', 'weekend'], 'category' => 'Painting', 'is_completed' => false, 'usage_count' => 1],
                ['title' => 'Install new light fixtures', 'description' => 'Replace old chandelier', 'tags' => ['electrical', 'lighting'], 'category' => 'Electrical', 'is_completed' => false, 'usage_count' => 1],
                ['title' => 'Clean gutters', 'description' => 'Remove leaves and debris', 'tags' => ['maintenance', 'seasonal'], 'category' => 'Maintenance', 'is_completed' => false, 'usage_count' => 4],
            ];
            
            foreach ($homeItems as $index => $item) {
                DB::table('list_items')->insert([
                    'reusable_list_id' => $homeList->id,
                    'created_by_user_id' => $user->id,
                    'title' => $item['title'],
                    'is_completed' => $item['is_completed'],
                    'completed_at' => $item['is_completed'] ? now()->subDays(rand(1, 14)) : null,
                    'completed_by_user_id' => $item['is_completed'] ? $user->id : null,
                    'usage_count' => $item['usage_count'],
                    'last_used_at' => now()->subDays(rand(1, 30)),
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Books to Read items
        $booksList = $lists->where('name', 'Books to Read')->first();
        if ($booksList) {
            $bookItems = [
                ['title' => 'The Seven Husbands of Evelyn Hugo', 'description' => 'By Taylor Jenkins Reid', 'tags' => ['fiction', 'romance'], 'category' => 'Fiction', 'is_completed' => true, 'usage_count' => 1],
                ['title' => 'Atomic Habits', 'description' => 'By James Clear', 'tags' => ['self-help', 'productivity'], 'category' => 'Self-Help', 'is_completed' => false, 'usage_count' => 2],
                ['title' => 'The Midnight Library', 'description' => 'By Matt Haig', 'tags' => ['fiction', 'philosophy'], 'category' => 'Fiction', 'is_completed' => false, 'usage_count' => 1],
                ['title' => 'Educated', 'description' => 'By Tara Westover', 'tags' => ['memoir', 'education'], 'category' => 'Biography', 'is_completed' => true, 'usage_count' => 1],
            ];
            
            foreach ($bookItems as $index => $item) {
                DB::table('list_items')->insert([
                    'reusable_list_id' => $booksList->id,
                    'created_by_user_id' => $user->id,
                    'title' => $item['title'],
                    'is_completed' => $item['is_completed'],
                    'completed_at' => $item['is_completed'] ? now()->subDays(rand(1, 60)) : null,
                    'completed_by_user_id' => $item['is_completed'] ? $user->id : null,
                    'usage_count' => $item['usage_count'],
                    'last_used_at' => now()->subDays(rand(1, 90)),
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
