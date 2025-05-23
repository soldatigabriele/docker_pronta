<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReusableList;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReusableListController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = ReusableList::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('shares', function ($shareQuery) use ($user) {
                      $shareQuery->where('shared_with_user_id', $user->id)
                                ->where('is_accepted', true);
                  });
            })
            ->with(['items' => function ($query) {
                $query->orderBy('sort_order')->orderBy('created_at');
            }, 'user:id,name,email'])
            ->orderBy('sort_order')
            ->orderBy('created_at');

        if ($request->has('include_shared')) {
            $query->with('sharedWith.sharedWith:id,name,email');
        }

        $lists = $query->get();

        return response()->json([
            'success' => true,
            'data' => $lists,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'icon' => 'nullable|string|max:50',
            'is_public' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['color'] = $validated['color'] ?? '#007AFF';
        $validated['icon'] = $validated['icon'] ?? 'list.bullet';

        $list = ReusableList::create($validated);
        $list->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'data' => $list,
            'message' => 'List created successfully',
        ], 201);
    }

    public function show(ReusableList $reusableList): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to access this list',
            ], 403);
        }

        $reusableList->load([
            'items' => function ($query) {
                $query->with('createdBy:id,name,email', 'completedBy:id,name,email')
                      ->orderBy('sort_order')
                      ->orderBy('created_at');
            },
            'user:id,name,email',
            'sharedWith.sharedWith:id,name,email'
        ]);

        return response()->json([
            'success' => true,
            'data' => $reusableList,
        ]);
    }

    public function update(Request $request, ReusableList $reusableList): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user, 'edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to edit this list',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'icon' => 'nullable|string|max:50',
            'is_public' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $reusableList->update($validated);
        $reusableList->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'data' => $reusableList,
            'message' => 'List updated successfully',
        ]);
    }

    public function destroy(ReusableList $reusableList): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user, 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this list',
            ], 403);
        }

        $reusableList->delete();

        return response()->json([
            'success' => true,
            'message' => 'List deleted successfully',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'list_ids' => 'required|array',
            'list_ids.*' => 'integer|exists:reusable_lists,id',
        ]);

        $user = Auth::user();

        foreach ($validated['list_ids'] as $index => $listId) {
            ReusableList::where('id', $listId)
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhereHas('shares', function ($shareQuery) use ($user) {
                              $shareQuery->where('shared_with_user_id', $user->id)
                                        ->where('is_accepted', true);
                          });
                })
                ->update(['sort_order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lists reordered successfully',
        ]);
    }
} 