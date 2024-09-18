<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleSocialiteController;
use Illuminate\Support\Facades\Log;
// use Auth;

Route::get('/', function () {
    return view('welcome');
});
Log::info('List messages');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/emails', [GoogleSocialiteController::class, 'listMessages'])->name('emails.index');

    Route::get('emails/{id}', [GoogleSocialiteController::class, 'showMessage'])->name('emails.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('auth/google', [GoogleSocialiteController::class, 'redirectToGoogle'])->name('google.redirect');  // redirect to google login
Route::get('/auth/google/callback', [GoogleSocialiteController::class, 'handleCallback'])->name('google.callback');

// Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.auth');
// Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');

require __DIR__ . '/auth.php';
