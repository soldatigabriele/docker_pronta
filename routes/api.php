<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ListItemController;
use App\Http\Controllers\Api\ListShareController;
use App\Http\Controllers\Api\ReusableListController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes for authentication
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes - require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Broadcasting authentication for WebSocket channels
    Route::post('broadcasting/auth', function () {
        return Broadcast::auth(request());
    });
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });
    
    // Reusable Lists
    Route::apiResource('lists', ReusableListController::class, [
        'parameters' => ['lists' => 'reusableList']
    ]);
    
    // List reordering
    Route::post('lists/reorder', [ReusableListController::class, 'reorder']);
    
    // List pinning
    Route::patch('lists/{reusableList}/pin', [ReusableListController::class, 'pin']);
    
    // List Items (nested under lists)
    Route::prefix('lists/{reusableList}')->group(function () {
        Route::apiResource('items', ListItemController::class, [
            'parameters' => ['items' => 'listItem']
        ]);
        
        // Item operations
        Route::patch('items/{listItem}/toggle-complete', [ListItemController::class, 'toggleComplete']);
        Route::post('items/reorder', [ListItemController::class, 'reorder']);
    });
    
    // Autocomplete for item titles
    Route::get('items/autocomplete', [ListItemController::class, 'autocomplete']);
    
    // List Sharing
    Route::prefix('lists/{reusableList}/shares')->group(function () {
        Route::get('/', [ListShareController::class, 'index']);
        Route::post('/', [ListShareController::class, 'store']);
        Route::patch('{listShare}', [ListShareController::class, 'update']);
        Route::delete('{listShare}', [ListShareController::class, 'destroy']);
    });
    
    // Share management for users
    Route::prefix('shares')->group(function () {
        Route::get('my-shares', [ListShareController::class, 'myShares']);
        Route::post('{listShare}/accept', [ListShareController::class, 'accept']);
        Route::post('{listShare}/decline', [ListShareController::class, 'decline']);
    });
    
});

/*
|--------------------------------------------------------------------------
| API Route Documentation
|--------------------------------------------------------------------------
|
| Authentication:
| POST   /api/auth/register         - Register new user
| POST   /api/auth/login            - Login user
| POST   /api/auth/logout           - Logout user (requires auth)
| GET    /api/auth/user             - Get current user (requires auth)
|
| Lists:
| GET    /api/lists                 - Get all user's lists (owned + shared)
| POST   /api/lists                 - Create new list
| GET    /api/lists/{id}            - Get specific list with items
| PUT    /api/lists/{id}            - Update list
| DELETE /api/lists/{id}            - Delete list
| POST   /api/lists/reorder         - Reorder lists
|
| Items:
| GET    /api/lists/{id}/items      - Get list items
| POST   /api/lists/{id}/items      - Create new item
| GET    /api/lists/{id}/items/{id} - Get specific item
| PUT    /api/lists/{id}/items/{id} - Update item
| DELETE /api/lists/{id}/items/{id} - Delete item
| PATCH  /api/lists/{id}/items/{id}/toggle-complete - Toggle completion
| POST   /api/lists/{id}/items/reorder - Reorder items
| GET    /api/items/autocomplete    - Get autocomplete suggestions
|
| Sharing:
| GET    /api/lists/{id}/shares     - Get list shares
| POST   /api/lists/{id}/shares     - Share list with user
| PUT    /api/lists/{id}/shares/{id} - Update share permissions
| DELETE /api/lists/{id}/shares/{id} - Remove share
| GET    /api/shares/my-shares      - Get user's pending/accepted shares
| POST   /api/shares/{id}/accept    - Accept share invitation
| POST   /api/shares/{id}/decline   - Decline share invitation
|
*/

