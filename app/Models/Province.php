<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $fillable = ['nombre'];

    public function localidades(): HasMany
    {
        return $this->hasMany(Localidad::class);
    }
}
