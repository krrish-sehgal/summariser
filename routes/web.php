<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleSocialiteController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

Route::get('/', function () {
    if (auth()->check()) {
        $controller = app()->make(GoogleSocialiteController::class);
        return $controller->listEmailHeaders();
    }

    return view('welcome'); // Render your custom view for unauthenticated users
})->name('emails.headers');

Log::info('List messages');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/emails', [GoogleSocialiteController::class, 'listMessages'])->name('emails.index');

    Route::get('emails/{id}', [GoogleSocialiteController::class, 'showMessage'])->name('emails.show');

});

Route::get('auth/google', [GoogleSocialiteController::class, 'redirectToGoogle'])->name('google.redirect');  // redirect to google login
Route::get('/auth/google/callback', [GoogleSocialiteController::class, 'handleCallback'])->name('google.callback');
Route::get('/daily_summaries', function () {
    return view('daily_summaries'); // This assumes you have a Blade template for daily_summaries
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

require __DIR__ . '/auth.php';



