<?php

use App\Http\Controllers\Api\EventController;
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

// SDK Event Ingestion - requires API key authentication
Route::middleware('verify.api.key')->group(function () {
    Route::post('/events', [EventController::class, 'store'])->name('api.events.store');
    Route::post('/events/batch', [EventController::class, 'storeBatch'])->name('api.events.store.batch');
});

// Public beacon validation endpoint
Route::get('/beacons/{beaconId}/validate', [EventController::class, 'validateBeacon'])
    ->name('api.beacons.validate');
