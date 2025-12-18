<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') - medicalshift</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <div class="flex items-center mb-2">
                    @if(file_exists(public_path('logo.png')))
                        <img src="{{ asset('logo.png') }}" alt="Logo" class="h-8 w-8 mr-2 object-contain">
                    @endif
                    <h1 class="text-2xl font-bold">Admin Panel</h1>
                </div>
                <p class="text-gray-400 text-sm">medicalshift</p>
            </div>
            <nav class="mt-8">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-chart-line w-5 mr-3"></i>
                    Dashboard
                </a>
                <a href="{{ route('admin.requests') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.requests*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-list w-5 mr-3"></i>
                    Requests
                </a>
                <a href="{{ route('admin.migrations') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.migrations') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-database w-5 mr-3"></i>
                    Migraciones
                </a>
                <a href="{{ route('admin.cache') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.cache') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-memory w-5 mr-3"></i>
                    Cache
                </a>
                <a href="{{ route('admin.jobs') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.jobs') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-tasks w-5 mr-3"></i>
                    Jobs
                </a>
            </nav>
            <div class="absolute bottom-0 w-64 p-4 border-t border-gray-700">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ auth()->user()->name ?? 'Admin' }}</p>
                        <p class="text-xs text-gray-400">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full bg-gray-700 hover:bg-gray-600 text-white py-2 px-4 rounded text-sm transition flex items-center justify-center">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <div class="p-8">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>


