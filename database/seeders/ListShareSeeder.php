<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListShareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create additional users for sharing examples
        $testUser = User::where('email', 'test@example.com')->first();
        
        $collaborator = User::firstOrCreate(
            ['email' => 'collaborator@example.com'],
            [
                'name' => 'Jane Collaborator',
                'password' => bcrypt('password'),
            ]
        );
        
        $viewer = User::firstOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'John Viewer',
                'password' => bcrypt('password'),
            ]
        );
        
        // Get lists that are marked as shared
        $sharedLists = DB::table('reusable_lists')
            ->where('user_id', $testUser->id)
            ->where('is_shared', true)
            ->get();
        
        foreach ($sharedLists as $list) {
            // Share with collaborator (edit permissions)
            DB::table('list_shares')->insert([
                'reusable_list_id' => $list->id,
                'shared_by_user_id' => $testUser->id,
                'shared_with_user_id' => $collaborator->id,
                'permission_level' => 'edit',
                'is_accepted' => true,
                'invited_at' => now()->subDays(rand(1, 30)),
                'accepted_at' => now()->subDays(rand(1, 25)),
                'can_share' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Share with viewer (view permissions only)
            DB::table('list_shares')->insert([
                'reusable_list_id' => $list->id,
                'shared_by_user_id' => $testUser->id,
                'shared_with_user_id' => $viewer->id,
                'permission_level' => 'view',
                'is_accepted' => true,
                'invited_at' => now()->subDays(rand(1, 20)),
                'accepted_at' => now()->subDays(rand(1, 15)),
                'can_share' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Create a pending share invitation
        $workList = DB::table('reusable_lists')
            ->where('user_id', $testUser->id)
            ->where('name', 'Work Tasks')
            ->first();
            
        if ($workList) {
            $pendingUser = User::firstOrCreate(
                ['email' => 'pending@example.com'],
                [
                    'name' => 'Pending User',
                    'password' => bcrypt('password'),
                ]
            );
            
            DB::table('list_shares')->insert([
                'reusable_list_id' => $workList->id,
                'shared_by_user_id' => $testUser->id,
                'shared_with_user_id' => $pendingUser->id,
                'permission_level' => 'edit',
                'is_accepted' => false,
                'invited_at' => now()->subDays(3),
                'accepted_at' => null,
                'expires_at' => now()->addDays(7), // Expires in 7 days
                'can_share' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
