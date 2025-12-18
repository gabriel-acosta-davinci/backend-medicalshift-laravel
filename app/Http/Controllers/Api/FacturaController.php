<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    /**
     * Listar facturas
     * GET /facturas?estado=Pendiente&limit=20
     */
    public function list(Request $request)
    {
        try {
            $estado = $request->query('estado');
            $limit = $request->query('limit', 50);
            $userId = $request->query('userId');

            // Si no viene userId, usar el del usuario autenticado
            $finalUserId = $userId ?? auth()->id();

            if (!$finalUserId) {
                return response()->json([
                    'error' => 'userId es requerido para listar facturas'
                ], 400);
            }

            $query = Factura::where('user_id', $finalUserId)
                ->orderBy('periodo', 'desc');

            if ($estado) {
                $query->where('estado', $estado);
            }

            $facturas = $query->limit($limit)->get();

            return response()->json([
                'message' => 'Facturas obtenidas correctamente',
                'count' => $facturas->count(),
                'facturas' => $facturas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
