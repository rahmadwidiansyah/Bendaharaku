<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\LoanController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleController; // Pastikan ini di-import

// Route Google Auth (Taruh di luar middleware 'auth')
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
Route::get('/', function () {
    return view('welcome');
});

// Semua route yang butuh login digabung jadi satu grup
Route::middleware(['auth'])->group(function () {

    // Dashboard & Analytics
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('verified')->name('dashboard');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Resources CRUD
    Route::resource('wallets', WalletController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('transactions', TransactionController::class);

    // Custom endpoint untuk tipe loan
    Route::get('/loans/{type}', [LoanController::class, 'index'])->name('loans.index');
});

Route::get('/test-error/{code}', function ($code) {
    abort($code);
});

require __DIR__ . '/auth.php';