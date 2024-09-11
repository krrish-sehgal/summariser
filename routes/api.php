
<?php

use App\Http\Controllers\NewsletterController;
Log::info('Request received:', $request->all());
Route::get('/newsletter', [NewsletterController::class, 'fetchNewsletter']);

