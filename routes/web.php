<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'index')->name('home');

// Games hub
Route::view('/games', 'games.index')->name('games.index');

// Jeopardy
use App\Http\Controllers\JeopardyController;

Route::prefix('games/jeopardy')->name('games.jeopardy.')->group(function () {
    // Public board listing
    Route::get('/', [JeopardyController::class, 'index'])->name('index');

    // Board builder (auth required)
    Route::middleware('auth')->group(function () {
        Route::get('/create', [JeopardyController::class, 'create'])->name('create');
        Route::get('/{board}/edit', [JeopardyController::class, 'edit'])->name('edit');
        Route::delete('/{board}', [JeopardyController::class, 'destroy'])->name('destroy');
        Route::post('/{board}/host', [JeopardyController::class, 'hostCreate'])->name('host.create');
        Route::get('/host/{code}', [JeopardyController::class, 'host'])->name('host');
    });

    // Join flow (guests welcome)
    Route::get('/join/{code}', [JeopardyController::class, 'joinShow'])->name('join');
    Route::post('/join/{code}', [JeopardyController::class, 'joinStore'])->name('join.store');
    Route::get('/play/{code}', [JeopardyController::class, 'play'])->name('play');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
