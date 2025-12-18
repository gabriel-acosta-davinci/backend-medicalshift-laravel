@extends('admin.layout')

@section('title', 'Request Logs')

@section('content')
<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Request Logs</h1>
        <form action="{{ route('admin.clear-old-logs') }}" method="POST" class="flex items-center space-x-2">
            @csrf
            <input type="number" name="days" value="30" min="1" class="border rounded px-3 py-1 w-20">
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Limpiar logs antiguos
            </button>
        </form>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Método</label>
                <select name="method" class="w-full border rounded px-3 py-2">
                    <option value="">Todos</option>
                    <option value="GET" {{ request('method') === 'GET' ? 'selected' : '' }}>GET</option>
                    <option value="POST" {{ request('method') === 'POST' ? 'selected' : '' }}>POST</option>
                    <option value="PUT" {{ request('method') === 'PUT' ? 'selected' : '' }}>PUT</option>
                    <option value="DELETE" {{ request('method') === 'DELETE' ? 'selected' : '' }}>DELETE</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Status Code</label>
                <input type="number" name="status_code" value="{{ request('status_code') }}" class="w-full border rounded px-3 py-2" placeholder="200, 404, etc.">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Path</label>
                <input type="text" name="path" value="{{ request('path') }}" class="w-full border rounded px-3 py-2" placeholder="/api/...">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de Requests -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Path</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiempo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($requests as $req)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded
                            {{ $req->method === 'GET' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $req->method === 'POST' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $req->method === 'PUT' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $req->method === 'DELETE' ? 'bg-red-100 text-red-800' : '' }}
                        ">
                            {{ $req->method }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <code class="text-sm text-gray-700">{{ $req->path }}</code>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ $req->user ? $req->user->email : 'Guest' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded
                            {{ $req->status_code >= 500 ? 'bg-red-100 text-red-800' : '' }}
                            {{ $req->status_code >= 400 && $req->status_code < 500 ? 'bg-orange-100 text-orange-800' : '' }}
                            {{ $req->status_code >= 200 && $req->status_code < 300 ? 'bg-green-100 text-green-800' : '' }}
                        ">
                            {{ $req->status_code }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $req->response_time }}ms
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $req->created_at->format('d/m/Y H:i:s') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="{{ route('admin.request-detail', $req->id) }}" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No hay requests registrados</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-6">
        {{ $requests->links() }}
    </div>
</div>
@endsection






