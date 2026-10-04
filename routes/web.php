<?php

use App\Http\Controllers\BacklogController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardMemberController;
use App\Http\Controllers\ClosedSprintController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\RestoredBoardController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('boards', BoardController::class);
    Route::resource('boards.issues', IssueController::class)
        ->shallow()
        ->only(['store', 'show', 'update', 'destroy']);
    Route::resource('issues.comments', CommentController::class)
        ->shallow()
        ->only(['store', 'update', 'destroy'])
        ->middleware('throttle:60,1');
    Route::resource('boards.labels', LabelController::class)
        ->shallow()
        ->only(['store', 'update', 'destroy']);
    Route::get('search', SearchController::class)->middleware('throttle:60,1')->name('search');
    Route::get('boards/{board}/backlog', [BacklogController::class, 'show'])->name('boards.backlog');
    Route::post('boards/{board}/sprints', [SprintController::class, 'store'])->name('boards.sprints.store');
    Route::get('boards/{board}/sprints/{sprint:slug}', [SprintController::class, 'show'])
        ->scopeBindings()
        ->name('boards.sprints.show');
    Route::post('boards/{board}/sprints/{sprint:slug}/closed', [ClosedSprintController::class, 'store'])
        ->scopeBindings()
        ->name('boards.sprints.closed.store');
    Route::post('boards/{board}/members', [BoardMemberController::class, 'store'])->name('boards.members.store');
    Route::put('boards/{board}/members/{member}', [BoardMemberController::class, 'update'])->name('boards.members.update');
    Route::delete('boards/{board}/members/{member}', [BoardMemberController::class, 'destroy'])->name('boards.members.destroy');
    Route::resource('users', UserController::class)->only(['index', 'show']);
    Route::post('boards/{board}/restore', [RestoredBoardController::class, 'store'])
        ->withTrashed()
        ->name('boards.restore');
});

require __DIR__.'/settings.php';
