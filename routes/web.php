<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\RestoredBoardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('boards', BoardController::class);
    Route::resource('boards.issues', IssueController::class)
        ->shallow()
        ->only(['store', 'show', 'update', 'destroy']);
    Route::post('boards/{board}/restore', [RestoredBoardController::class, 'store'])
        ->withTrashed()
        ->name('boards.restore');
});

require __DIR__.'/settings.php';
