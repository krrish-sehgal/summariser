
<x-app-layout>
    <div class="container">
        <h1>Email Details</h1>
        <div>
            <h2>{{ $message->getPayload()->getHeaders()[0]->getValue() }}</h2> <!-- Example: Fetch the email subject -->
            <p><strong>From:</strong> {{ $message->getPayload()->getHeaders()[1]->getValue() }}</p> <!-- Example: Fetch the sender -->
            <p><strong>Body:</strong></p>
            <div>
                {!! nl2br(e($message->getPayload()->getBody()->getData())) !!} <!-- Display the email body -->
            </div>
        </div>
    </div>
</x-app-layout>
