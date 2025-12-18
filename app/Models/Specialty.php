<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Specialty extends Model
{
    protected $fillable = ['nombre', 'tipo'];

    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class, 'provider_specialty');
    }
}
