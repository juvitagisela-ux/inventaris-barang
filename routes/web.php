<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Inventory\Index as InventoryIndex;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1')->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/', fn () => auth()->check()
    ? redirect()->route('inventory.index')
    : redirect()->route('login'));

Route::get('/inventaris', InventoryIndex::class)
    ->middleware('auth')
    ->name('inventory.index');
