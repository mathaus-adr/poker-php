<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\RoomController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', LoginController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('rooms', [RoomController::class, 'create']);
    Route::put('rooms/{id}/join', [RoomController::class, 'join']);
    Route::post('rooms/{id}/start', [RoomController::class, 'startGame']);
});
