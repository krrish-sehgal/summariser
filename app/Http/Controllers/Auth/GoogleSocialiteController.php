<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use App\Services\GoogleService;
use Illuminate\Container\Attributes\Log as AttributesLog;

class GoogleSocialiteController extends Controller
{
    protected $googleService;

    public function __construct(GoogleService $googleService)
    {
        $this->googleService = $googleService;
    }

    // Redirect to Google for authentication
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email', 'https://www.googleapis.com/auth/gmail.modify'])  // Gmail scope added
            ->redirect();
    }

    // Handle the callback after Google authentication
    public function handleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $finduser = User::where('social_id', $googleUser->id)->first();

            if ($finduser) {
                // User exists, log them in
                Auth::login($finduser);

                // Update user's Google token for Gmail API access
                $finduser->update([
                    'google_token' => json_encode($googleUser->token),
                ]);

                // Regenerate session and redirect to the dashboard
                session()->regenerate();
                return redirect()->intended('/dashboard');
            } else {
                // User doesn't exist, create a new one
                $newUser = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'social_id' => $googleUser->id,
                    'social_type' => 'google',
                    'google_token' => json_encode($googleUser->token), // Store Google token
                    'password' => bcrypt('my-google'), // Temporary password for social login
                ]);

                // Log in the new user and regenerate session
                Auth::login($newUser);
                session()->regenerate();
                return redirect()->intended('/dashboard');
            }
        } catch (\Exception $e) {
            Log::error('Error during Google login:', ['error' => $e->getMessage()]);
            return redirect()->route('login')->with('error', 'Authentication failed. Please try again.');
        }
    }

    // List Gmail messages using the stored Google token
    public function listMessages()
    {   
        Log::info('List messages');
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Decode the Google token stored in the database
            $token = json_decode($user->google_token, true);

            // Set the access token in GoogleService for API access
            $this->googleService->setAccessToken($token);

            // Fetch the Gmail messages
            $messages = $this->googleService->listMessages();
            dump($messages);
            // Pass the messages to the view
            return view('emails.index', ['messages' => $messages]);
        } catch (\Exception $e) {
            Log::error('Error fetching Gmail messages:', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to retrieve Gmail messages.');
        }
    }
}
