<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Socialite Routes must be outside api prefix to work smoothly with browser redirects
Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [AuthController::class, 'callback']);

// Vue SPA catch-all (Make sure it's the very last route)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api\/).*$');
