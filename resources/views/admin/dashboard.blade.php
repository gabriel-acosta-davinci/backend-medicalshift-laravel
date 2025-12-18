@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<div>
    <h1 class="text-3xl font-bold mb-6">Dashboard</h1>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Usuarios</p>
                    <p class="text-3xl font-bold">{{ $stats['total_users'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Gestiones</p>
                    <p class="text-3xl font-bold">{{ $stats['total_gestiones'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-alt text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Requests Hoy</p>
                    <p class="text-3xl font-bold">{{ $stats['requests_today'] }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-purple-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Tiempo Promedio</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['avg_response_time'], 0) }}ms</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Requests Recientes -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Requests Recientes</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse($recentRequests as $req)
                    <div class="flex items-center justify-between border-b pb-3">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded
                                    {{ $req->method === 'GET' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $req->method === 'POST' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $req->method === 'PUT' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $req->method === 'DELETE' ? 'bg-red-100 text-red-800' : '' }}
                                ">
                                    {{ $req->method }}
                                </span>
                                <span class="text-sm text-gray-600">{{ $req->path }}</span>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                {{ $req->created_at->diffForHumans() }} • 
                                <span class="font-semibold {{ $req->status_code >= 400 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $req->status_code }}
                                </span>
                                • {{ $req->response_time }}ms
                            </div>
                        </div>
                        <a href="{{ route('admin.request-detail', $req->id) }}" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                    @empty
                    <p class="text-gray-500 text-center py-4">No hay requests recientes</p>
                    @endforelse
                </div>
                <div class="mt-4">
                    <a href="{{ route('admin.requests') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                        Ver todos →
                    </a>
                </div>
            </div>
        </div>

        <!-- Requests por Método -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Requests por Método</h2>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($requestsByMethod as $method)
                    <div class="flex items-center justify-between">
                        <span class="font-medium">{{ $method->method }}</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-32 bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($method->count / $stats['total_requests']) * 100 }}%"></div>
                            </div>
                            <span class="text-sm text-gray-600 w-12 text-right">{{ $method->count }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Rutas -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Rutas Más Solicitadas</h2>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($topRoutes as $route)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-mono text-gray-700">{{ $route->path }}</span>
                        <span class="text-sm font-semibold">{{ $route->count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Status Codes -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Status Codes</h2>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($requestsByStatus as $status)
                    <div class="flex items-center justify-between">
                        <span class="px-3 py-1 rounded text-sm font-semibold
                            {{ $status->status_code >= 500 ? 'bg-red-100 text-red-800' : '' }}
                            {{ $status->status_code >= 400 && $status->status_code < 500 ? 'bg-orange-100 text-orange-800' : '' }}
                            {{ $status->status_code >= 200 && $status->status_code < 300 ? 'bg-green-100 text-green-800' : '' }}
                        ">
                            {{ $status->status_code }}
                        </span>
                        <span class="text-sm font-semibold">{{ $status->count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection






