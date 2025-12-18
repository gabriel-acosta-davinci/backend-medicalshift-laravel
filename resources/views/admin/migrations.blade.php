@extends('admin.layout')

@section('title', 'Migraciones')

@section('content')
<div>
    <h1 class="text-3xl font-bold mb-6">Migraciones</h1>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Migration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($migrations as $migration)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $migration->id }}</td>
                    <td class="px-6 py-4">
                        <code class="text-sm text-gray-700">{{ $migration->migration }}</code>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $migration->batch }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">No hay migraciones</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection






