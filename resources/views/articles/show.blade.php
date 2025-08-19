@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <div class="bg-white shadow-md rounded-lg p-6">
        <h1 class="text-2xl font-bold mb-4">Article: {{ $article->title }}</h1>

        {{-- Article Meta --}}
        <div class="mb-4">
            <p><strong>Author:</strong> {{ $article->author }}</p>
            <p><strong>Category:</strong> {{ $article->category }}</p>
            <p><strong>Thumbnail:</strong> 
                @if($article->thumbnail)
                    <img src="{{ $article->thumbnail }}" class="h-16 w-16 inline-block rounded" />
                @else
                    <span class="text-gray-500">No thumbnail</span>
                @endif
            </p>
        </div>

        {{-- Article Body Preview --}}
        <div class="mb-6">
            <h2 class="text-lg font-semibold">Body</h2>
            <div class="prose max-w-none">
                {!! Str::limit($article->body, 500, '...') !!}
            </div>
        </div>

        {{-- Compliance & Publishing --}}
        <form method="POST" action="{{ route('articles.publish', $article->id) }}">
            @csrf
            <label for="feed" class="block text-sm font-medium">Choose Feed:</label>
            <select name="feed" id="feed" class="border rounded p-2 mb-4">
                @foreach($feeds as $feed)
                    <option value="{{ $feed->name }}">{{ $feed->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow">
                Run Compliance & Publish
            </button>
        </form>

        {{-- Compliance Results --}}
        @if(session('compliance_result'))
            <div class="mt-6">
                <h3 class="text-lg font-semibold">Compliance Check Result</h3>
                @if(session('compliance_result')['status'] === 'success')
                    <div class="bg-green-100 text-green-800 p-3 rounded">
                        ✅ {{ session('compliance_result')['message'] }}
                    </div>
                @else
                    <div class="bg-red-100 text-red-800 p-3 rounded">
                        ❌ Failed Compliance:
                        <ul class="list-disc ml-6">
                            @foreach(session('compliance_result')['errors'] as $error)
                                <li><strong>{{ $error['validator'] }}</strong>: {{ $error['message'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        {{-- Audit Logs --}}
        <div class="mt-8">
            <h3 class="text-lg font-semibold mb-2">Audit Logs</h3>
            <table class="min-w-full border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 border">Date</th>
                        <th class="px-4 py-2 border">Feed</th>
                        <th class="px-4 py-2 border">Status</th>
                        <th class="px-4 py-2 border">Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $log)
                        <tr>
                            <td class="border px-4 py-2">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="border px-4 py-2">{{ $log->feed->name }}</td>
                            <td class="border px-4 py-2">
                                @if($log->status === 'SUCCESS')
                                    <span class="text-green-600 font-semibold">SUCCESS</span>
                                @else
                                    <span class="text-red-600 font-semibold">FAIL</span>
                                @endif
                            </td>
                            <td class="border px-4 py-2">{{ $log->message }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500">No logs yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
