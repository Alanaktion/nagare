<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');

    Route::resource('boards', BoardController::class)
        ->middlewareFor('create', 'precognitive')
        ->middlewareFor('update', 'precognitive');

    Route::resource('issues', IssueController::class)
        ->middlewareFor('create', 'precognitive')
        ->middlewareFor('update', 'precognitive');

    Route::resource('users', UserController::class)
        ->only(['index', 'show']);

    Route::get('/boards/{board}/{sprint:slug}', [BoardController::class, 'showSprint'])
        ->name('boards.show-sprint')
        ->middleware('can:view,board');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
