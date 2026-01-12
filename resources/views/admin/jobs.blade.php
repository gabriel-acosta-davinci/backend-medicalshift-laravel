@extends('admin.layout')

@section('title', 'Jobs')

@section('content')
<div>
    <h1 class="text-3xl font-bold mb-6">Jobs</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Jobs Pendientes -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">Jobs Pendientes ({{ $pendingJobs->count() }})</h2>
            </div>
            <div class="p-6">
                @if($pendingJobs->count() > 0)
                <div class="space-y-4">
                    @foreach($pendingJobs as $job)
                    <div class="border-b pb-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">{{ $job->queue }}</p>
                                <p class="text-sm text-gray-500">Attempts: {{ $job->attempts }}</p>
                            </div>
                            <span class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::createFromTimestamp($job->created_at)->format('d/m/Y H:i') }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500 text-center py-4">No hay jobs pendientes</p>
                @endif
            </div>
        </div>

        <!-- Jobs Fallidos -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-red-600">Jobs Fallidos ({{ $failedJobs->count() }})</h2>
            </div>
            <div class="p-6">
                @if($failedJobs->count() > 0)
                <div class="space-y-4">
                    @foreach($failedJobs as $job)
                    <div class="border-b pb-3">
                        <div>
                            <p class="font-medium">{{ $job->queue }}</p>
                            <p class="text-sm text-gray-500">Connection: {{ $job->connection }}</p>
                            <p class="text-xs text-red-600 mt-2">
                                {{ \Carbon\Carbon::parse($job->failed_at)->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500 text-center py-4">No hay jobs fallidos</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection









