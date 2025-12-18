@extends('admin.layout')

@section('title', 'Request Detail')

@section('content')
<div>
    <div class="mb-6">
        <a href="{{ route('admin.requests') }}" class="text-blue-600 hover:text-blue-800">
            ← Volver a Requests
        </a>
    </div>

    <h1 class="text-3xl font-bold mb-6">Detalle del Request</h1>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Información General</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-500">Método</label>
                <p class="text-lg font-semibold">{{ $requestLog->method }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Path</label>
                <p class="text-lg font-mono">{{ $requestLog->path }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Status Code</label>
                <p class="text-lg font-semibold">{{ $requestLog->status_code }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Response Time</label>
                <p class="text-lg font-semibold">{{ $requestLog->response_time }}ms</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">IP Address</label>
                <p class="text-lg">{{ $requestLog->ip_address }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Usuario</label>
                <p class="text-lg">{{ $requestLog->user ? $requestLog->user->email : 'Guest' }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">Fecha</label>
                <p class="text-lg">{{ $requestLog->created_at->format('d/m/Y H:i:s') }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-500">User Agent</label>
                <p class="text-sm text-gray-600">{{ $requestLog->user_agent }}</p>
            </div>
        </div>
    </div>

    @if($requestLog->request_body)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Request Body</h2>
        <pre class="bg-gray-100 p-4 rounded overflow-x-auto text-sm">{{ json_encode(json_decode($requestLog->request_body), JSON_PRETTY_PRINT) }}</pre>
    </div>
    @endif

    @if($requestLog->response_body)
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">Response Body</h2>
        <pre class="bg-gray-100 p-4 rounded overflow-x-auto text-sm">{{ json_encode(json_decode($requestLog->response_body), JSON_PRETTY_PRINT) }}</pre>
    </div>
    @endif
</div>
@endsection






