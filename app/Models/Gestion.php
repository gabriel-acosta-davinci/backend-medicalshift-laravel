<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gestion extends Model
{
    protected $table = 'gestiones';

    protected $fillable = [
        'user_id',
        'estado',
        'fecha',
        'nombre',
        'document_path',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }

    /**
     * Relationship with user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener la URL completa del documento
     * 
     * @return string|null URL del documento o null si no existe
     */
    public function getDocumentUrlAttribute(): ?string
    {
        if (!$this->document_path) {
            return null;
        }

        // Si el documento existe, devolver la URL para accederlo
        // Usar la ruta de la API para descargar el documento
        return url("/api/gestiones/{$this->id}/document");
    }

    /**
     * Verificar si la gestión tiene un documento asociado
     * 
     * @return bool
     */
    public function hasDocument(): bool
    {
        return !empty($this->document_path);
    }
}
