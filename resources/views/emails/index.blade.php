<x-app-layout>
    <div class="container">
        <h1>Emails</h1>
        <ul>
            @foreach ($messages as $message)
                <li>{{ $message->getId() }}</li>
            @endforeach
        </ul>
    </div>
</x-app-layout>
