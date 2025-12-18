<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Address;
use App\Models\Factura;
use App\Models\Gestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Limpiar tablas (en orden para respetar foreign keys)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Gestion::truncate();
        Factura::truncate();
        Address::truncate();
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Leer archivos JSON
        $usersJsonPath = base_path('database/seeders/data/users.json');
        $facturasJsonPath = base_path('database/seeders/data/facturas.json');
        $gestionesJsonPath = base_path('database/seeders/data/gestiones.json');

        // Verificar que los archivos existan
        if (!file_exists($usersJsonPath)) {
            $this->command->error("Archivo users.json no encontrado en: {$usersJsonPath}");
            return;
        }

        $usersData = json_decode(file_get_contents($usersJsonPath), true);
        $facturasData = file_exists($facturasJsonPath) ? json_decode(file_get_contents($facturasJsonPath), true) : [];
        $gestionesData = file_exists($gestionesJsonPath) ? json_decode(file_get_contents($gestionesJsonPath), true) : [];

        // Mapa para relacionar documentNumber con user_id
        $userMap = [];

        // Crear usuarios
        $this->command->info('Creando usuarios...');
        foreach ($usersData as $userData) {
            // Buscar si ya existe un usuario con este document_number
            $user = User::where('document_number', $userData['documentNumber'])->first();

            if (!$user) {
                $user = User::create([
                    'name' => $userData['fullName'] ?? $userData['name'] ?? 'Usuario',
                    'email' => $userData['email'],
                    'document_number' => $userData['documentNumber'],
                    'phone_number' => $userData['phoneNumber'] ?? null,
                    'date_of_birth' => isset($userData['dateOfBirth']) ? \Carbon\Carbon::parse($userData['dateOfBirth'])->format('Y-m-d') : null,
                    'marital_status' => $userData['maritalStatus'] ?? null,
                    'cbu' => $userData['cbu'] ?? null,
                    'associate_number' => $userData['associateNumber'] ?? null,
                    'plan' => $userData['plan'] ?? null,
                    'password' => Hash::make('password123'), // Contraseña por defecto
                    'password_updated_at' => now(),
                ]);
            }

            // Guardar el mapeo
            $userMap[$userData['documentNumber']] = $user->id;

            // Crear dirección si existe
            if (isset($userData['address']) && is_array($userData['address'])) {
                $address = $userData['address'];
                Address::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'street' => $address['street'] ?? null,
                        'number' => $address['number'] ?? null,
                        'floor' => $address['floor'] ?? null,
                        'apartment' => $address['apartment'] ?? null,
                        'city' => $address['city'] ?? null,
                        'province' => $address['province'] ?? null,
                    ]
                );
            }

            $this->command->info("Usuario creado: {$user->email}");
        }

        // Crear facturas
        $this->command->info('Creando facturas...');
        foreach ($facturasData as $facturaData) {
            $userId = $userMap[$facturaData['userId']] ?? null;

            if (!$userId) {
                $this->command->warn("Usuario no encontrado para factura: {$facturaData['userId']}");
                continue;
            }

            // Convertir periodo de "Noviembre 2025" a "2025-11"
            $periodo = $this->parsePeriodo($facturaData['periodo']);

            Factura::create([
                'user_id' => $userId,
                'estado' => $facturaData['estado'],
                'periodo' => $periodo,
                'monto' => $facturaData['monto'] ?? null,
            ]);

            $this->command->info("Factura creada para usuario: {$facturaData['userId']}");
        }

        // Crear gestiones
        $this->command->info('Creando gestiones...');
        foreach ($gestionesData as $gestionData) {
            $userId = $userMap[$gestionData['userId']] ?? null;

            if (!$userId) {
                $this->command->warn("Usuario no encontrado para gestión: {$gestionData['userId']}");
                continue;
            }

            // Convertir fecha de "DD/MM/YYYY" a datetime
            $fecha = $this->parseFecha($gestionData['fecha']);

            Gestion::create([
                'user_id' => $userId,
                'nombre' => $gestionData['nombre'],
                'estado' => $gestionData['estado'],
                'fecha' => $fecha,
                'document_path' => null, // Se puede agregar después cuando se suban los documentos
            ]);

            $this->command->info("Gestión creada: {$gestionData['nombre']}");
        }

        $this->command->info('¡Seed completado exitosamente!');
    }

    /**
     * Convertir periodo de formato "Noviembre 2025" a "2025-11"
     */
    private function parsePeriodo(string $periodo): string
    {
        // Mapeo de meses en español a números
        $meses = [
            'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04',
            'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08',
            'septiembre' => '09', 'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12'
        ];

        $periodo = strtolower($periodo);
        foreach ($meses as $mes => $numero) {
            if (strpos($periodo, $mes) !== false) {
                // Extraer el año
                preg_match('/\d{4}/', $periodo, $matches);
                $año = $matches[0] ?? date('Y');
                return "{$año}-{$numero}";
            }
        }

        // Si no se encuentra, devolver el formato actual o el mes actual
        return date('Y-m');
    }

    /**
     * Convertir fecha de formato "DD/MM/YYYY" a Carbon datetime
     */
    private function parseFecha(string $fecha): Carbon
    {
        // Formato: "10/11/2025"
        $parts = explode('/', $fecha);
        if (count($parts) === 3) {
            $day = (int)$parts[0];
            $month = (int)$parts[1];
            $year = (int)$parts[2];
            return Carbon::create($year, $month, $day);
        }

        // Si no se puede parsear, usar fecha actual
        return Carbon::now();
    }
}
