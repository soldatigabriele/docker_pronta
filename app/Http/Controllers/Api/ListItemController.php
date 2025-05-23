<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListItem;
use App\Models\ReusableList;
use App\Models\ItemUsageStat;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListItemController extends Controller
{
    public function index(Request $request, ReusableList $reusableList): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to access this list',
            ], 403);
        }

        $query = $reusableList->items()
            ->with('createdBy:id,name,email', 'completedBy:id,name,email');

        // Filter by completion status if specified
        if ($request->has('completed')) {
            $query->where('is_completed', $request->boolean('completed'));
            
            // Sort based on completion status
            if ($request->boolean('completed')) {
                $query->orderBy('usage_count', 'desc')
                      ->orderBy('last_used_at', 'desc');
            } else {
                $query->orderBy('sort_order')
                      ->orderBy('created_at');
            }
        } else {
            // When fetching all items, we need custom sorting
            // First get all items, then sort them properly
            $items = $query->get();
            
            // Separate pending and completed items
            $pendingItems = $items->filter(fn($item) => !$item->is_completed)
                                 ->sortBy(['sort_order', 'created_at'])
                                 ->values();
            
            $completedItems = $items->filter(fn($item) => $item->is_completed)
                                   ->sortByDesc('usage_count')
                                   ->sortByDesc('last_used_at')
                                   ->values();
            
            // Combine them: pending items first, then completed items
            $sortedItems = $pendingItems->concat($completedItems);
            
            return response()->json([
                'success' => true,
                'data' => $sortedItems,
            ]);
        }

        $items = $query->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request, ReusableList $reusableList): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user, 'edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to add items to this list',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sort_order' => 'integer|min:0',
        ]);

        // Check if this item was previously created by this user
        $existingItem = $this->findPreviouslyCreatedItem($user, $validated['title']);
        
        if ($existingItem) {
            // Pull back the previously created item
            $existingItem->update([
                'reusable_list_id' => $reusableList->id,
                'is_completed' => false,
                'completed_at' => null,
                'completed_by_user_id' => null,
                'usage_count' => $existingItem->usage_count + 1,
                'last_used_at' => now(),
            ]);

            $item = $existingItem;
            $message = 'Item restored from previous use';
        } else {
            // Create new item
            $validated['reusable_list_id'] = $reusableList->id;
            $validated['created_by_user_id'] = $user->id;
            $validated['usage_count'] = 1;
            $validated['last_used_at'] = now();

            $item = ListItem::create($validated);
            $message = 'Item created successfully';
        }

        // Update usage statistics
        ItemUsageStat::createOrUpdateStat($user, $validated['title']);

        $item->load('createdBy:id,name,email');

        return response()->json([
            'success' => true,
            'data' => $item,
            'message' => $message,
        ], 201);
    }

    public function show(ReusableList $reusableList, ListItem $listItem): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to access this list',
            ], 403);
        }

        if ($listItem->reusable_list_id !== $reusableList->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in this list',
            ], 404);
        }

        $listItem->load('createdBy:id,name,email', 'completedBy:id,name,email');

        return response()->json([
            'success' => true,
            'data' => $listItem,
        ]);
    }

    public function update(Request $request, ReusableList $reusableList, ListItem $listItem): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user, 'edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to edit items in this list',
            ], 403);
        }

        if ($listItem->reusable_list_id !== $reusableList->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in this list',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'sort_order' => 'integer|min:0',
        ]);

        $listItem->update($validated);
        $listItem->load('createdBy:id,name,email', 'completedBy:id,name,email');

        return response()->json([
            'success' => true,
            'data' => $listItem,
            'message' => 'Item updated successfully',
        ]);
    }

    public function destroy(ReusableList $reusableList, ListItem $listItem): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user, 'edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete items from this list',
            ], 403);
        }

        if ($listItem->reusable_list_id !== $reusableList->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in this list',
            ], 404);
        }

        $listItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully',
        ]);
    }

    public function toggleComplete(Request $request, ReusableList $reusableList, ListItem $listItem): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user, 'edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to modify items in this list',
            ], 403);
        }

        if ($listItem->reusable_list_id !== $reusableList->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in this list',
            ], 404);
        }

        if ($listItem->is_completed) {
            $listItem->markIncomplete();
            $message = 'Item marked as incomplete';
        } else {
            $listItem->markCompleted($user);
            
            // Update usage statistics
            $stat = ItemUsageStat::createOrUpdateStat($user, $listItem->title);
            $stat->incrementCompletion();
            
            $message = 'Item marked as complete';
        }

        $listItem->load('createdBy:id,name,email', 'completedBy:id,name,email');

        return response()->json([
            'success' => true,
            'data' => $listItem,
            'message' => $message,
        ]);
    }

    public function reorder(Request $request, ReusableList $reusableList): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user, 'edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to reorder items in this list',
            ], 403);
        }

        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:list_items,id',
        ]);

        foreach ($validated['item_ids'] as $index => $itemId) {
            ListItem::where('id', $itemId)
                ->where('reusable_list_id', $reusableList->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Items reordered successfully',
        ]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $suggestions = ItemUsageStat::where('user_id', $user->id)
            ->where('item_title', 'LIKE', "%{$query}%")
            ->orderBy('usage_count', 'desc')
            ->orderBy('completion_rate', 'desc')
            ->limit(10)
            ->get(['item_title', 'usage_count', 'completion_rate']);

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    private function findPreviouslyCreatedItem(User $user, string $title): ?ListItem
    {
        return ListItem::where('created_by_user_id', $user->id)
            ->where('title', $title)
            ->where('is_completed', true)
            ->orderBy('last_used_at', 'desc')
            ->first();
    }
} 