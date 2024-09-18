<?php

namespace App\Http\Controllers\Auth;

use Stevebauman\Hypertext\Transformer;
use GeminiAPI\Laravel\Facades\Gemini;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use App\Services\GoogleService;
use Illuminate\Container\Attributes\Log as AttributesLog;
use Carbon\Carbon;
use DOMDocument;


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


    private function formatForAPI($input) {
        // Remove new lines and carriage returns
        $formatted = str_replace(array("\r", "\n"), '', $input);
    
        // Trim leading/trailing spaces
        $formatted = trim($formatted);
    
        // Optional: Escape double quotes, if needed for Postman or JSON
        $formatted = addslashes($formatted);
    
        return $formatted;
    }
    public function listMessages(Request $request)
{
    try {
        // Get the authenticated user
        $user = Auth::user();

        // Decode the Google token stored in the database
        $token = json_decode($user->google_token, true);

        // Set the access token in GoogleService for API access
        $this->googleService->setAccessToken($token);

        // List all messages
        $messages = $this->googleService->listMessages();
        
        $duration = $request->input('duration', 1); // Default to 1 day if not provided
        $cutOffDate = Carbon::now()->subDays($duration)->format('Y-m-d');
        Log::info($cutOffDate);
        
        // Fetch details for each message
        $emailDetails = [];
        $loop = 0;
        foreach ($messages as $message) {
            Log::info('This is an informational message.');

            $messageId = $message->getId();  // Get the ID of each message

            // Use the getMessage function to fetch full message details
            $email = $this->googleService->getMessage($messageId);

            // Extract the necessary headers and body
            $headers = $email->getPayload()->getHeaders();
            $body = $email->getPayload()->getBody();

            $from = $this->getHeader($headers, 'From');
            $date = $this->getHeader($headers, 'Date');
            $subject = $this->getHeader($headers, 'Subject');

            // Check if the date is within the specified duration
            try {
                $emailDate = new \DateTime($date);  // Default format detection
            } catch (\Exception $e) {
                Log::error('Failed to parse date', ['date' => $date, 'error' => $e->getMessage()]);
                continue; // Skip this email if date parsing fails
            }

            if ($emailDate) {
                Log::info('emails date', ['emails' => $emailDate], ['cutOffDate' => $cutOffDate]);

                // If the email is older than the specified duration, stop processing further emails
                if ($emailDate < new \DateTime($cutOffDate)) {
                    break;
                }

                // If the body is empty, check for parts (e.g., in case of multi-part messages)
                if (!$body || !$body->getData()) {
                    // Check if the message has parts (e.g., multi-part emails)
                    $parts = $email->getPayload()->getParts();

                    foreach ($parts as $part) {
                        // Usually, the plain text body has mimeType 'text/plain' or 'text/html'
                        if ($part->getMimeType() === 'text/plain') {
                            $body = $part->getBody();
                            break;  // Stop at the first text/plain part
                        } 
                    }
                }

                // Decode the body content if found
                $emailBody = $body && $body->getData() ? base64_decode($body->data,$strict = false) : 'No body content available';

                
                // dump($emailBody);
                // // Clean the body content to remove non-text elements
                // $html = new \Html2Text\Html2Text($emailBody);
                // $cleanedBody = $html->getText();
                
                $transformer = new Transformer();
                $cleanedBody = $transformer->keepNewLines()->keepLinks()->toText($emailBody);
                
                $cleanedBody =  preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
                '|[\x00-\x7F][\x80-\xBF]+'.
                '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
                '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
                '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
                '', $cleanedBody);

                $cleanedBody = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]'.
                '|\xED[\xA0-\xBF][\x80-\xBF]/S','', $cleanedBody );

                $cleanedBody = $this->formatForAPI($cleanedBody);


                // Call the external API to summarize the email body
                $summary = $this->callSummaryAPI($cleanedBody);

                // Store the email details in an array
                $emailDetails[] = [
                    'from' => $from,
                    'date' => $date,
                    'subject' => $subject,
                    'body' => $cleanedBody,
                    'summary' => $summary,  // Add the summary to the details
                ];
            }
            $loop++;
            if($loop >= 10) {
                break;
            }
        }
        dump($emailDetails);
        $this->sendSummaryEmail($emailDetails,$user);
            
        // Pass the email details to the view
        return view('emails.index', ['emails' => $emailDetails]);

    } catch (\Exception $e) {
        Log::error('Error fetching Gmail messages:', ['error' => $e->getMessage()]);
        return redirect()->back()->with('error', 'Failed to retrieve Gmail messages.');
    }
}
private function sendSummaryEmail($emailDetails,$recipient)
{
    // Initialize the email content with an empty string
    $summaryContent = "Here are the summaries of the unread emails:\n\n";

    foreach ($emailDetails as $email) {
        $summaryContent .= "Subject: {$email['subject']}\nFrom: {$email['from']}\nSummary: {$email['summary']}\n\n";
    }

    // Create a new SendGrid Mail object
    $email = new \SendGrid\Mail\Mail();
    
    // Set the sender's information
    $email->setFrom("krrishsehgal03@gmail.com", "Email-Summariser");

    // Set the email subject
    $email->setSubject("Daily Unread Email Summaries");

    // Add the recipient's email address (e.g., user's email)
    $email->addTo($recipient->email, $recipient->name);  // Replace with the actual recipient's email

    // Set the email content (plain text and HTML versions)
    $email->addContent("text/plain", $summaryContent);
    $email->addContent("text/html", nl2br($summaryContent));  // Convert new lines to <br> for HTML content

    // Send the email using SendGrid
    $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));  // Make sure your API key is stored in environment variables

    try {
        // Attempt to send the email
        $response = $sendgrid->send($email);
        
        // Log the response for debugging
        Log::info('Email sent successfully', [
            'status_code' => $response->statusCode(),
            'headers' => $response->headers(),
            'body' => $response->body()
        ]);
        
    } catch (Exception $e) {
        // Handle the exception and log the error message
        Log::error('Failed to send email', ['error' => $e->getMessage()]);
        echo 'Caught exception: ' . $e->getMessage() . "\n";
    }
}

private function callSummaryAPI(string $emailText): ?string
{
    try {
        // Define the system prompt
        $system_prompt = "You are a language model that summarizes emails in 3-5 bullet points.Dont include any links ";
        
        // Define the user input with the email text
        $user_input = "Summarize the following in less than 100 words and only using 3-5 bullet points: {$emailText}";
        
        // Define the style prompt
        $style = "Powerpoint slide with 3-5 bullet points";

        // Create the prompt that combines system prompt, user input, and style
        $full_prompt = "{$system_prompt}\n\n{$style}\n\n{$user_input}";

        // Use the Gemini class to generate the summary based on the full prompt
        $summary = Gemini::generateText($full_prompt);

        // Ensure the response is not empty or null
        if ($summary) {
            return $summary;
        } else {
            Log::error('Gemini API returned an empty response.');
            return null;
        }
    } catch (\Exception $e) {
        // Log the exception if something goes wrong
        Log::error('Error calling the Gemini API: ' . $e->getMessage());
        return null;
    }
}


// Function to clean the email body and remove non-text elements
private function cleanEmailBody($emailBody)
{
    // Remove base64-encoded strings (images, attachments)


    // Remove HTML tags
    $doc = new DOMDocument();
    @$doc->loadHTML($body);
    dump($doc);
    $body = $doc->textContent;
    // Further cleanup if needed
    // ...

    return $body;
}
    
    // Helper function to get specific headers by name
    private function getHeader($headers, $name)
    {
        foreach ($headers as $header) {
            if ($header->getName() === $name) {
                return $header->getValue();
            }
        }
        return null;
    }
     public function showMessage($id)
    {
        try {
            $user = Auth::user();
            $token = json_decode($user->google_token, true);
            $this->googleService->setAccessToken($token);

            $message = $this->googleService->getMessage($id);

            return view('emails.show', ['message' => $message]);
        } catch (\Exception $e) {
            Log::error('Error fetching Gmail message:', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to retrieve Gmail message.');
        }
    }
}
