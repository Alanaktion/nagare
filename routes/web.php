<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/user/settings', [UserController::class, 'settings']);
    Route::get('/dashboard', [BoardController::class, 'index'])->name('dashboard');
    Route::get('/boards/{board:slug}', [BoardController::class, 'show'])->middleware('can:view,board');
    Route::get('/boards/{board:slug}/{sprint}', [BoardController::class, 'showSprint'])->middleware('can:view,board');
});
