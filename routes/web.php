<?php

use Illuminate\Support\Facades\Route;

// Named login route for API redirects
Route::get('/login', function() {
    return response()->json(['message' => 'Unauthorized'], 401);
})->name('login');

Route::get('{any?}', function() {
    return view('application');
})->where('any', '.*');
