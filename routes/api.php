<?php

use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\StoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::middleware('can:view,board')->group(function () {
        // General board types
        Route::get('/boards/{board}/statuses', [StatusController::class, 'showBoard']);
        Route::post('/boards/{board}/cards', [CardController::class, 'create'])->middleware('can:update,board');

        // Kanban boards
        Route::get('/boards/{board}/cards', [CardController::class, 'showBoard']);

        // Scrum boards
        Route::get('/boards/{board}/stories/{sprint}', [StoryController::class, 'showSprint']);
        Route::get('/boards/{board}/cards/{sprint}', [CardController::class, 'showSprint']);
    });
});
