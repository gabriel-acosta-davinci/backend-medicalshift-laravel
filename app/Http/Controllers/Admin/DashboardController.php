<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Gestion;
use App\Models\Factura;
use App\Models\RequestLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Mostrar el dashboard principal
     */
    public function index()
    {
        // Estadísticas generales
        $stats = [
            'total_users' => User::count(),
            'total_gestiones' => Gestion::count(),
            'total_facturas' => Factura::count(),
            'total_requests' => RequestLog::count(),
            'requests_today' => RequestLog::whereDate('created_at', today())->count(),
            'requests_this_week' => RequestLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'avg_response_time' => RequestLog::avg('response_time') ?? 0,
        ];

        // Requests recientes (últimas 10)
        $recentRequests = RequestLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Migraciones
        $migrations = DB::table('migrations')
            ->orderBy('id', 'desc')
            ->get();

        // Cache entries
        $cacheEntries = DB::table('cache')
            ->orderBy('expiration', 'desc')
            ->limit(20)
            ->get();

        // Jobs pendientes
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        // Estadísticas de requests por método
        $requestsByMethod = RequestLog::select('method', DB::raw('count(*) as count'))
            ->groupBy('method')
            ->get();

        // Estadísticas de requests por status code
        $requestsByStatus = RequestLog::select('status_code', DB::raw('count(*) as count'))
            ->groupBy('status_code')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Rutas más solicitadas
        $topRoutes = RequestLog::select('path', DB::raw('count(*) as count'))
            ->groupBy('path')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Usuarios más activos
        $activeUsers = RequestLog::select('user_id', DB::raw('count(*) as count'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $item->user = User::find($item->user_id);
                return $item;
            });

        return view('admin.dashboard', compact(
            'stats',
            'recentRequests',
            'migrations',
            'cacheEntries',
            'pendingJobs',
            'failedJobs',
            'requestsByMethod',
            'requestsByStatus',
            'topRoutes',
            'activeUsers'
        ));
    }

    /**
     * Ver logs de requests
     */
    public function requests(Request $request)
    {
        $query = RequestLog::with('user');

        // Filtros
        if ($request->has('method') && $request->method) {
            $query->where('method', $request->method);
        }

        if ($request->has('status_code') && $request->status_code) {
            $query->where('status_code', $request->status_code);
        }

        if ($request->has('path') && $request->path) {
            $query->where('path', 'like', '%' . $request->path . '%');
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.requests', compact('requests'));
    }

    /**
     * Ver detalle de un request
     */
    public function requestDetail($id)
    {
        $requestLog = RequestLog::with('user')->findOrFail($id);

        return view('admin.request-detail', compact('requestLog'));
    }

    /**
     * Ver migraciones
     */
    public function migrations()
    {
        $migrations = DB::table('migrations')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.migrations', compact('migrations'));
    }

    /**
     * Ver cache
     */
    public function cache()
    {
        $cacheEntries = DB::table('cache')
            ->orderBy('expiration', 'desc')
            ->paginate(50);

        return view('admin.cache', compact('cacheEntries'));
    }

    /**
     * Ver jobs
     */
    public function jobs()
    {
        $pendingJobs = DB::table('jobs')
            ->orderBy('created_at', 'desc')
            ->get();

        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->get();

        return view('admin.jobs', compact('pendingJobs', 'failedJobs'));
    }

    /**
     * Limpiar cache
     */
    public function clearCache()
    {
        Cache::flush();
        
        return redirect()->route('admin.dashboard')
            ->with('success', 'Cache limpiado exitosamente');
    }

    /**
     * Eliminar logs antiguos
     */
    public function clearOldLogs(Request $request)
    {
        $days = $request->input('days', 30);
        
        $deleted = RequestLog::where('created_at', '<', now()->subDays($days))->delete();

        return redirect()->route('admin.requests')
            ->with('success', "Se eliminaron {$deleted} logs antiguos (más de {$days} días)");
    }
}
