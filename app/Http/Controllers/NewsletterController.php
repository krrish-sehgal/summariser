<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function fetchNewsletter(Request $request)
    {
        // Fetch the newsletter data, example response
        return response()->json(['message' => 'Newsletter fetched successfully']);
    }
}

