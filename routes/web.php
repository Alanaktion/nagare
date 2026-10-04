<?php

use App\Http\Controllers\BacklogController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ClosedSprintController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\RestoredBoardController;
use App\Http\Controllers\SprintController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('boards', BoardController::class);
    Route::resource('boards.issues', IssueController::class)
        ->shallow()
        ->only(['store', 'show', 'update', 'destroy']);
    Route::get('boards/{board}/backlog', [BacklogController::class, 'show'])->name('boards.backlog');
    Route::post('boards/{board}/sprints', [SprintController::class, 'store'])->name('boards.sprints.store');
    Route::get('boards/{board}/sprints/{sprint:slug}', [SprintController::class, 'show'])
        ->scopeBindings()
        ->name('boards.sprints.show');
    Route::post('boards/{board}/sprints/{sprint:slug}/closed', [ClosedSprintController::class, 'store'])
        ->scopeBindings()
        ->name('boards.sprints.closed.store');
    Route::post('boards/{board}/restore', [RestoredBoardController::class, 'store'])
        ->withTrashed()
        ->name('boards.restore');
});

require __DIR__.'/settings.php';
