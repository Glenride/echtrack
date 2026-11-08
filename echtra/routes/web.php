<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\BeaconController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Beacons
    Route::resource('beacons', BeaconController::class);
    Route::get('beacons/{beacon}/download', [BeaconController::class, 'downloadWatermarked'])
        ->name('beacons.download');

    // API Keys
    Route::get('api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');
});

require __DIR__.'/settings.php';
