<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'document_number',
        'phone_number',
        'date_of_birth',
        'marital_status',
        'cbu',
        'associate_number',
        'plan',
        'password_updated_at',
        'is_admin',
        'digital_token',
        'digital_token_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'password_updated_at' => 'datetime',
            'is_admin' => 'boolean',
            'digital_token_active' => 'boolean',
        ];
    }

    /**
     * Get the user's address as an array (accessor for compatibility)
     */
    public function getAddressAttribute(): ?array
    {
        $address = $this->address()->first();
        if (!$address) {
            return [
                'street' => '',
                'number' => 0,
                'floor' => '',
                'apartment' => '',
                'city' => '',
                'province' => '',
            ];
        }
        return [
            'street' => $address->street ?? '',
            'number' => $address->number ?? 0,
            'floor' => $address->floor ?? '',
            'apartment' => $address->apartment ?? '',
            'city' => $address->city ?? '',
            'province' => $address->province ?? '',
        ];
    }

    /**
     * Transformar atributos a camelCase al serializar para el frontend
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        // Transformar campos snake_case a camelCase
        $transformed = [];
        foreach ($array as $key => $value) {
            // Convertir snake_case a camelCase
            // Ejemplo: document_number -> documentNumber
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));
            $transformed[$camelKey] = $value;
        }
        
        // Asegurar que address se transforme correctamente si existe
        if (isset($transformed['address']) && is_array($transformed['address'])) {
            $addressTransformed = [];
            foreach ($transformed['address'] as $key => $value) {
                $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));
                $addressTransformed[$camelKey] = $value;
            }
            $transformed['address'] = $addressTransformed;
        }
        
        return $transformed;
    }

    /**
     * Get the identifier for JWT authentication
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT
     */
    public function getJWTCustomClaims()
    {
        return [
            'document_number' => $this->document_number,
            'type' => 'custom',
        ];
    }

    /**
     * Relationship with address (hasOne for primary address, but can be changed to hasMany if needed)
     */
    public function address()
    {
        return $this->hasOne(Address::class);
    }

    /**
     * Relationship with addresses (if user can have multiple addresses)
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Relationship with gestiones
     */
    public function gestiones()
    {
        return $this->hasMany(Gestion::class);
    }

    /**
     * Relationship with facturas
     */
    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }

    /**
     * Generar un token digital de 3 dígitos aleatorio y seguro
     */
    public function generateDigitalToken(): string
    {
        // Generar un número aleatorio de 3 dígitos (000-999)
        // Usar random_int para mayor seguridad criptográfica
        $token = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
        return $token;
    }

    /**
     * Activar la generación automática del token digital
     */
    public function activateDigitalToken(): void
    {
        $this->digital_token_active = true;
        $this->digital_token = $this->generateDigitalToken();
        $this->save();
    }

    /**
     * Desactivar la generación automática del token digital
     */
    public function deactivateDigitalToken(): void
    {
        $this->digital_token_active = false;
        $this->digital_token = null;
        $this->save();
    }

    /**
     * Regenerar el token digital (solo si está activo)
     */
    public function regenerateDigitalToken(): void
    {
        if ($this->digital_token_active) {
            $newToken = $this->generateDigitalToken();
            // Usar update directo en la base de datos para evitar problemas de caché
            static::where('id', $this->id)
                ->update([
                    'digital_token' => $newToken,
                    'updated_at' => now()
                ]);
            // Actualizar el atributo en la instancia actual
            $this->digital_token = $newToken;
        }
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification);
    }
}
