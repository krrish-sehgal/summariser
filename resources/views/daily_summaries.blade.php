<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daily Summaries - Email Summarizer</title>
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}">

</head>
<body>
  <div class="navbar">
    <h2>Email Summarizer - Daily Summaries</h2>
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
    <div class="daily-archive-section">
      <h3>Daily Archive</h3>
      <ul id="dailyArchiveList">
          @foreach ($emails as $email)
              <li >
                  <strong>Subject:</strong> {{ $email['subject'] }}<br>
                  <strong>Summary:</strong> <pre class="whitespace-pre-wrap">{{ $email['summary'] }}</pre>
              </li>
              <hr class="my-4 border-gray-600">
          @endforeach
      </ul>
    </div>
  </div>

  <script src="app.js"></script>
</body>
</html>
