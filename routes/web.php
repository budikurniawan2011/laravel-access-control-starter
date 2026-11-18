<?php

use App\Http\Controllers\SetupController;
use Filament\Http\Middleware\SetUpPanel;
use App\Models\InstallationState;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('installation_states')) {
        return redirect()->route('setup');
    }

    return redirect(
        InstallationState::query()->whereKey(1)->value('setup_completed')
            ? '/admin'
            : '/setup',
    );
})->name('home');

Route::get('/setup', [SetupController::class, 'create'])
    ->middleware(SetUpPanel::class.':admin')
    ->name('setup');
Route::post('/setup', [SetupController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('setup.store');
