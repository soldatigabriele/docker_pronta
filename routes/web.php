<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

// Broadcasting Authentication for WebSocket connections
Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
