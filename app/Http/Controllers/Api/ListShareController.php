<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListShare;
use App\Models\ReusableList;
use App\Models\User;
use App\Events\ListShared;
use App\Events\ListUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ListShareController extends Controller
{
    public function index(ReusableList $reusableList): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user, 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view shares for this list',
            ], 403);
        }

        $shares = $reusableList->shares()
            ->with('sharedWith:id,name,email', 'sharedBy:id,name,email')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $shares,
        ]);
    }

    public function store(Request $request, ReusableList $reusableList): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user, 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to share this list',
            ], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'permission_level' => 'required|in:view,edit,admin',
            'can_share' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $targetUser = User::where('email', $validated['email'])->first();

        if ($targetUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot share list with yourself',
            ], 422);
        }

        // Check if already shared
        $existingShare = $reusableList->shares()
            ->where('shared_with_user_id', $targetUser->id)
            ->first();

        if ($existingShare) {
            return response()->json([
                'success' => false,
                'message' => 'List is already shared with this user',
            ], 422);
        }

        $share = ListShare::create([
            'reusable_list_id' => $reusableList->id,
            'shared_by_user_id' => $user->id,
            'shared_with_user_id' => $targetUser->id,
            'permission_level' => $validated['permission_level'],
            'can_share' => $validated['can_share'] ?? false,
            'expires_at' => $validated['expires_at'] ?? null,
            'invited_at' => now(),
        ]);

        // Update list as shared
        if (!$reusableList->is_shared) {
            $reusableList->update(['is_shared' => true]);
        }

        $share->load('sharedWith:id,name,email', 'sharedBy:id,name,email');

        // Broadcast the share event to the target user
        broadcast(new ListShared($share));

        // Broadcast list update to all users with access
        broadcast(new ListUpdated($reusableList));

        return response()->json([
            'success' => true,
            'data' => $share,
            'message' => 'List shared successfully',
        ], 201);
    }

    public function update(Request $request, ReusableList $reusableList, ListShare $listShare): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user, 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to modify shares for this list',
            ], 403);
        }

        if ($listShare->reusable_list_id !== $reusableList->id) {
            return response()->json([
                'success' => false,
                'message' => 'Share not found for this list',
            ], 404);
        }

        $validated = $request->validate([
            'permission_level' => 'sometimes|required|in:view,edit,admin',
            'can_share' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $listShare->update($validated);
        $listShare->load('sharedWith:id,name,email', 'sharedBy:id,name,email');

        // Broadcast list update to all users with access
        broadcast(new ListUpdated($reusableList));

        return response()->json([
            'success' => true,
            'data' => $listShare,
            'message' => 'Share updated successfully',
        ]);
    }

    public function destroy(ReusableList $reusableList, ListShare $listShare): JsonResponse
    {
        $user = Auth::user();

        if (!$reusableList->canUserAccess($user, 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to remove shares from this list',
            ], 403);
        }

        if ($listShare->reusable_list_id !== $reusableList->id) {
            return response()->json([
                'success' => false,
                'message' => 'Share not found for this list',
            ], 404);
        }

        $listShare->delete();

        // Check if list should no longer be marked as shared
        if ($reusableList->shares()->count() === 0) {
            $reusableList->update(['is_shared' => false]);
        }

        // Broadcast list update to all users with access
        broadcast(new ListUpdated($reusableList));

        return response()->json([
            'success' => true,
            'message' => 'Share removed successfully',
        ]);
    }

    public function accept(ListShare $listShare): JsonResponse
    {
        $user = Auth::user();

        if ($listShare->shared_with_user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to accept this share',
            ], 403);
        }

        if ($listShare->is_accepted) {
            return response()->json([
                'success' => false,
                'message' => 'Share is already accepted',
            ], 422);
        }

        if ($listShare->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Share invitation has expired',
            ], 422);
        }

        $listShare->accept();
        $listShare->load('reusableList', 'sharedBy:id,name,email');

        // Broadcast list update to all users with access
        broadcast(new ListUpdated($listShare->reusableList));

        return response()->json([
            'success' => true,
            'data' => $listShare,
            'message' => 'Share accepted successfully',
        ]);
    }

    public function decline(ListShare $listShare): JsonResponse
    {
        $user = Auth::user();

        if ($listShare->shared_with_user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to decline this share',
            ], 403);
        }

        $listShare->delete();

        return response()->json([
            'success' => true,
            'message' => 'Share declined successfully',
        ]);
    }

    public function myShares(): JsonResponse
    {
        $user = Auth::user();

        $pendingShares = ListShare::where('shared_with_user_id', $user->id)
            ->where('is_accepted', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->with('reusableList', 'sharedBy:id,name,email')
            ->orderBy('invited_at', 'desc')
            ->get();

        $acceptedShares = ListShare::where('shared_with_user_id', $user->id)
            ->where('is_accepted', true)
            ->with('reusableList', 'sharedBy:id,name,email')
            ->orderBy('accepted_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'pending' => $pendingShares,
                'accepted' => $acceptedShares,
            ],
            'debug' => [
                'current_user_id' => $user->id,
                'current_user_name' => $user->name,
                'current_user_email' => $user->email,
                'pending_count' => $pendingShares->count(),
                'accepted_count' => $acceptedShares->count(),
            ],
        ]);
    }
} 