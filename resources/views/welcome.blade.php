<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inbox - Email Summarizer</title>
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}">

</head>
<body>
<div class="navbar">
  <h2>Email Summarizer - Inbox</h2>
  <a href="{{ url('/') }}" class="btn-nav">Inbox</a>
  <a href="{{ url('/emails') }}" class="btn-nav">Daily Summaries</a>
  @if(Auth::check())
    <!-- User is logged in, show the Logout button -->
    <form id="logoutForm" action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" id="logoutBtn" class="btn-logout">Logout</button>
    </form>
@else
    <!-- User is logged out, show the Login button -->
    <a href="{{ url('/auth/google') }}" class="btn-logout">Login with Google</a>
@endif

</div>


  <div class="container">
    <div class="inbox-section">
      <h3>Inbox</h3>
    <div class="container">
        @if (Auth::check())
            @if (isset($emails) && count($emails) > 0)
                <ul>
                    @foreach ($emails as $email)
                        <li>
                            <strong>From:</strong> {{ $email['from'] }} <br>
                            <strong>Date:</strong> {{ $email['date'] }} <br>
                            <strong>Subject:</strong> {{ $email['subject'] }}
                        </li>
                    @endforeach
                </ul>
            @else
                <p>You have no emails to display.</p>
            @endif

        @else
            <p>Please<a href="{{ url('/auth/google') }}" > Log in with Google </a>to view your emails.</p>
        @endif
    </div>
    </div>
  </div>
  
  
</body>
</html>
