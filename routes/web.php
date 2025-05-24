<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

// Broadcasting Authentication for WebSocket connections
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// SPA Catch-all route - handles all routes for Vue Router
Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*');

Route::get('/home', function () {
    return view('welcome');
})->where('any', '.*');
Route::get('/list', function () {
    return view('welcome');
})->where('any', '.*');
Route::get('/login', function () {
    return view('welcome');
})->where('any', '.*');
