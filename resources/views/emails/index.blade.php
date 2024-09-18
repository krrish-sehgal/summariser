<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Emails') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="container bg-gray-800 p-4 rounded-lg text-white">
                        <h1 class="text-2xl font-semibold mb-4">Emails</h1>
                        <ul class="list-disc pl-5">
                            @foreach ($emails as $email)
                                <li class="mb-4">
                                    <strong>Subject:</strong> {{ $email['subject'] }}<br>
                                    <strong>Summary:</strong> <pre class="whitespace-pre-wrap">{{ $email['summary'] }}</pre>
                                </li>
                                <hr class="my-4 border-gray-600">
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
