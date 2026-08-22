<?php

use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\LeadController;
use Illuminate\Support\Facades\Route;

// All admin CRUD routes sit behind Sanctum. The public bot webhook (phase 2)
// will live outside this group with its own signature verification.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', fn (\Illuminate\Http\Request $r) => $r->user());

    Route::apiResource('cars', CarController::class);
    Route::post('cars/{car}/photos', [CarController::class, 'uploadPhoto']);
    Route::delete('cars/{car}/photos/{photo}', [CarController::class, 'deletePhoto']);

    Route::apiResource('leads', LeadController::class)->only(['index', 'show','store', 'update']);
    Route::post('leads/{lead}/conversations', [ConversationController::class, 'store']);
});
