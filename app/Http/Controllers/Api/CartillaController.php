<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Localidad;
use App\Models\Specialty;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartillaController extends Controller
{
    /**
     * Obtener todas las provincias
     * GET /api/cartilla/provinces
     */
    public function getProvinces()
    {
        $provinces = Province::orderBy('nombre')->get(['id', 'nombre']);
        return response()->json(['provinces' => $provinces]);
    }

    /**
     * Obtener localidades por provincia
     * GET /api/cartilla/localidades?province_id=1
     */
    public function getLocalidades(Request $request)
    {
        $provinceId = $request->query('province_id');
        
        if ($provinceId) {
            $localidades = Localidad::where('province_id', $provinceId)
                ->orderBy('nombre')
                ->get(['id', 'nombre']);
        } else {
            $localidades = Localidad::with('province')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'province_id']);
        }
        
        return response()->json(['localidades' => $localidades]);
    }

    /**
     * Obtener especialidades por tipo
     * GET /api/cartilla/specialties?type=medic
     */
    public function getSpecialties(Request $request)
    {
        $type = $request->query('type');
        
        $query = Specialty::query();
        if ($type) {
            $query->where('tipo', $type);
        }
        
        $specialties = $query->orderBy('nombre')->get(['id', 'nombre', 'tipo']);
        return response()->json(['specialties' => $specialties]);
    }

    /**
     * Buscar providers por tipo, especialidad, localidad
     * GET /api/cartilla/providers?type=medic&specialty=...&localidad=...
     */
    public function searchProviders(Request $request)
    {
        $type = $request->query('type'); // medic, diagnostic, urgency, inpatient, odontology
        $specialty = $request->query('specialty');
        $localidad = $request->query('localidad'); // nombre de localidad
        $plan = $request->query('plan'); // bronce, plata, oro, platino

        $query = Provider::with(['localidad.province', 'specialties']);

        if ($type) {
            $query->where('tipo', $type);
        }

        if ($localidad) {
            $query->whereHas('localidad', function ($q) use ($localidad) {
                $q->where('nombre', $localidad);
            });
        }

        if ($specialty) {
            $query->whereHas('specialties', function ($q) use ($specialty) {
                $q->where('nombre', $specialty);
            });
        }

        if ($plan) {
            $query->whereExists(function ($q) use ($plan) {
                $q->select(DB::raw(1))
                    ->from('provider_plan')
                    ->whereColumn('provider_plan.provider_id', 'providers.id')
                    ->where('provider_plan.plan', $plan);
            });
        }

        $providers = $query->get()->map(function ($provider) {
            return [
                'id' => $provider->id,
                'nombre' => $provider->nombre,
                'direccion' => $provider->direccion,
                'telefono' => $provider->telefono,
                'localidad' => $provider->localidad->nombre ?? null,
                'institucion' => $provider->institucion,
            ];
        });

        return response()->json(['providers' => $providers]);
    }

    /**
     * Buscar profesionales (tipo especial con nombre e institución)
     * GET /api/cartilla/professionals?specialty=...&localidad=...&nombre=...&plan=...
     */
    public function searchProfessionals(Request $request)
    {
        $specialty = $request->query('specialty');
        $localidad = $request->query('localidad');
        $nombre = $request->query('nombre'); // nombre del profesional o institución
        $plan = $request->query('plan');

        $query = Provider::with(['localidad', 'specialties'])
            ->where('tipo', 'medic'); // Los profesionales son tipo medic

        if ($localidad) {
            $query->whereHas('localidad', function ($q) use ($localidad) {
                $q->where('nombre', $localidad);
            });
        }

        if ($specialty) {
            $query->whereHas('specialties', function ($q) use ($specialty) {
                $q->where('nombre', $specialty);
            });
        }

        if ($nombre) {
            $query->where(function ($q) use ($nombre) {
                $q->where('nombre', 'like', "%{$nombre}%")
                    ->orWhere('institucion', 'like', "%{$nombre}%");
            });
        }

        if ($plan) {
            $query->whereExists(function ($q) use ($plan) {
                $q->select(DB::raw(1))
                    ->from('provider_plan')
                    ->whereColumn('provider_plan.provider_id', 'providers.id')
                    ->where('provider_plan.plan', $plan);
            });
        }

        $professionals = $query->get()->map(function ($provider) {
            return [
                'id' => $provider->id,
                'nombre' => $provider->nombre,
                'especialidad' => $provider->specialties->first()->nombre ?? null,
                'institucion' => $provider->institucion,
                'direccion' => $provider->direccion,
                'localidad' => $provider->localidad->nombre ?? null,
                'telefono' => $provider->telefono,
            ];
        });

        return response()->json(['professionals' => $professionals]);
    }

    /**
     * Buscar farmacias
     * GET /api/cartilla/pharmacies?plan=...&localidad=...
     */
    public function searchPharmacies(Request $request)
    {
        $plan = $request->query('plan');
        $localidad = $request->query('localidad');

        $query = Provider::with('localidad')
            ->where('tipo', 'pharmacy');

        if ($localidad) {
            $query->whereHas('localidad', function ($q) use ($localidad) {
                $q->where('nombre', $localidad);
            });
        }

        if ($plan) {
            $query->whereExists(function ($q) use ($plan) {
                $q->select(DB::raw(1))
                    ->from('provider_plan')
                    ->whereColumn('provider_plan.provider_id', 'providers.id')
                    ->where('provider_plan.plan', $plan);
            });
        }

        $pharmacies = $query->get()->map(function ($provider) {
            return [
                'id' => $provider->id,
                'nombre' => $provider->nombre,
                'direccion' => $provider->direccion,
                'telefono' => $provider->telefono,
                'localidad' => $provider->localidad->nombre ?? null,
            ];
        });

        return response()->json(['pharmacies' => $pharmacies]);
    }

    /**
     * Buscar vacunatorios
     * GET /api/cartilla/vaccines?localidad=...
     */
    public function searchVaccines(Request $request)
    {
        $localidad = $request->query('localidad');

        $query = Provider::with('localidad')
            ->where('tipo', 'vaccine');

        if ($localidad) {
            $query->whereHas('localidad', function ($q) use ($localidad) {
                $q->where('nombre', $localidad);
            });
        }

        $vaccines = $query->get()->map(function ($provider) {
            return [
                'id' => $provider->id,
                'nombre' => $provider->nombre,
                'direccion' => $provider->direccion,
                'telefono' => $provider->telefono,
                'localidad' => $provider->localidad->nombre ?? null,
            ];
        });

        return response()->json(['vaccines' => $vaccines]);
    }

    /**
     * Buscar providers agrupados por especialidad (para compatibilidad con estructura JSON anterior)
     * GET /api/cartilla/providers-grouped?type=medic&localidad=...&plan=...
     */
    public function searchProvidersGrouped(Request $request)
    {
        $type = $request->query('type');
        $localidad = $request->query('localidad');
        $plan = $request->query('plan');

        $query = Provider::with(['localidad', 'specialties'])
            ->where('tipo', $type);

        if ($localidad) {
            $query->whereHas('localidad', function ($q) use ($localidad) {
                $q->where('nombre', $localidad);
            });
        }

        if ($plan) {
            $query->whereExists(function ($q) use ($plan) {
                $q->select(DB::raw(1))
                    ->from('provider_plan')
                    ->whereColumn('provider_plan.provider_id', 'providers.id')
                    ->where('provider_plan.plan', $plan);
            });
        }

        $providers = $query->get();

        // Agrupar por localidad y especialidad (similar a estructura JSON)
        $grouped = [];
        foreach ($providers as $provider) {
            $locName = $provider->localidad->nombre ?? 'Sin localidad';
            if (!isset($grouped[$locName])) {
                $grouped[$locName] = [];
            }

            foreach ($provider->specialties as $specialty) {
                $specName = $specialty->nombre;
                if (!isset($grouped[$locName][$specName])) {
                    $grouped[$locName][$specName] = [];
                }

                $grouped[$locName][$specName][] = [
                    'nombre' => $provider->nombre,
                    'direccion' => $provider->direccion,
                    'telefono' => $provider->telefono,
                ];
            }
        }

        return response()->json($grouped);
    }
}
