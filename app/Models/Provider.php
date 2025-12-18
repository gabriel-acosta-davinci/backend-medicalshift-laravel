<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Provider extends Model
{
    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'localidad_id',
        'tipo',
        'institucion',
    ];

    public function localidad(): BelongsTo
    {
        return $this->belongsTo(Localidad::class);
    }

    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'provider_specialty');
    }

    public function getPlansAttribute(): array
    {
        return \DB::table('provider_plan')
            ->where('provider_id', $this->id)
            ->pluck('plan')
            ->toArray();
    }

    public function hasPlan(string $plan): bool
    {
        return \DB::table('provider_plan')
            ->where('provider_id', $this->id)
            ->where('plan', $plan)
            ->exists();
    }
}
