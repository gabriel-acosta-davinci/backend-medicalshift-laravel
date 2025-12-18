@extends('admin.layout')

@section('title', 'Cache')

@section('content')
<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Cache</h1>
        <form action="{{ route('admin.clear-cache') }}" method="POST">
            @csrf
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Limpiar Cache
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Key</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiration</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($cacheEntries as $entry)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <code class="text-sm text-gray-700">{{ $entry->key }}</code>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ \Carbon\Carbon::createFromTimestamp($entry->expiration)->format('d/m/Y H:i:s') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" class="px-6 py-4 text-center text-gray-500">No hay entradas en cache</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $cacheEntries->links() }}
    </div>
</div>
@endsection






