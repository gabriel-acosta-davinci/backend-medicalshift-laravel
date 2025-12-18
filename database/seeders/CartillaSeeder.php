<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Province;
use App\Models\Localidad;
use App\Models\Specialty;
use App\Models\Provider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CartillaSeeder extends Seeder
{
    /**
     * Ruta base para los archivos JSON del frontend
     */
    private $jsonBasePath;

    public function __construct()
    {
        // Ruta relativa desde backend-medicalshift-laravel a medicalshift/src/data
        $this->jsonBasePath = base_path('../parcial-2-pd-acn4av-acosta-chavez-cariaga/medicalshift/src/data');
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Iniciando importación de datos de cartilla...');

        // 1. Importar provincias y localidades
        $this->importProvincesAndLocalidades();

        // 2. Importar especialidades
        $this->importSpecialties();

        // 3. Importar providers
        $this->importProviders();

        $this->command->info('¡Importación de cartilla completada!');
    }

    /**
     * Importar provincias y localidades desde localidades.json
     */
    private function importProvincesAndLocalidades(): void
    {
        $this->command->info('Importando provincias y localidades...');

        $filePath = $this->jsonBasePath . '/localidades.json';
        if (!File::exists($filePath)) {
            $this->command->error("Archivo no encontrado: {$filePath}");
            return;
        }

        $data = json_decode(File::get($filePath), true);

        foreach ($data as $provinceName => $localidades) {
            // Crear o obtener provincia
            $province = Province::firstOrCreate(['nombre' => $provinceName]);

            // Crear localidades
            foreach ($localidades as $localidadName) {
                Localidad::firstOrCreate([
                    'nombre' => $localidadName,
                    'province_id' => $province->id,
                ]);
            }
        }

        $this->command->info('Provincias y localidades importadas correctamente.');
    }

    /**
     * Importar especialidades desde los archivos JSON
     */
    private function importSpecialties(): void
    {
        $this->command->info('Importando especialidades...');

        $specialtyFiles = [
            'medicSpecialties.json' => 'medic',
            'diagnosticSpecialties.json' => 'diagnostic',
            'urgencySpecialties.json' => 'urgency',
            'inpatientSpecialties.json' => 'inpatient',
            'odontologySpecialties.json' => 'odontology',
        ];

        foreach ($specialtyFiles as $fileName => $type) {
            $filePath = $this->jsonBasePath . '/' . $fileName;
            if (!File::exists($filePath)) {
                $this->command->warn("Archivo no encontrado: {$filePath}");
                continue;
            }

            $specialties = json_decode(File::get($filePath), true);
            foreach ($specialties as $specialtyName) {
                Specialty::firstOrCreate([
                    'nombre' => $specialtyName,
                    'tipo' => $type,
                ]);
            }
        }

        $this->command->info('Especialidades importadas correctamente.');
    }

    /**
     * Importar providers desde los archivos providersBy*
     */
    private function importProviders(): void
    {
        $this->command->info('Importando providers...');

        // Providers por tipo médico (con especialidades)
        $this->importProvidersByType('providersByMedic.json', 'medic');
        $this->importProvidersByType('providersByDiagnostic.json', 'diagnostic');
        $this->importProvidersByType('providersByUrgency.json', 'urgency');
        $this->importProvidersByType('providersByInpatient.json', 'inpatient');
        $this->importProvidersByType('providersByOdontology.json', 'odontology');
        
        // Providers sin especialidades (farmacias y vacunatorios)
        $this->importProvidersSimple('providersByPharmacy.json', 'pharmacy');
        $this->importProvidersSimple('providersByVaccine.json', 'vaccine');

        // Importar profesionales desde professionals.json
        $this->importProfessionals();

        $this->command->info('Providers importados correctamente.');
    }

    /**
     * Importar providers desde un archivo providersBy* (estructura: {localidad: {especialidad: [providers]}})
     */
    private function importProvidersByType(string $fileName, string $type): void
    {
        $filePath = $this->jsonBasePath . '/' . $fileName;
        if (!File::exists($filePath)) {
            $this->command->warn("Archivo no encontrado: {$filePath}");
            return;
        }

        $data = json_decode(File::get($filePath), true);
        $count = 0;

        foreach ($data as $localidadName => $specialties) {
            $localidad = Localidad::where('nombre', $localidadName)->first();
            if (!$localidad) {
                $this->command->warn("Localidad no encontrada: {$localidadName}");
                continue;
            }

            foreach ($specialties as $specialtyName => $providers) {
                // Si providers es un string (mensaje de error), saltarlo
                if (is_string($providers)) {
                    continue;
                }

                $specialty = Specialty::where('nombre', $specialtyName)
                    ->where('tipo', $type)
                    ->first();

                if (!$specialty) {
                    // Si no existe la especialidad, crearla
                    $specialty = Specialty::create([
                        'nombre' => $specialtyName,
                        'tipo' => $type,
                    ]);
                }

                foreach ($providers as $providerData) {
                    // Crear o obtener provider
                    $provider = Provider::firstOrCreate(
                        [
                            'nombre' => $providerData['nombre'],
                            'direccion' => $providerData['direccion'],
                            'localidad_id' => $localidad->id,
                            'tipo' => $type,
                        ],
                        [
                            'telefono' => $providerData['telefono'] ?? null,
                            'institucion' => $providerData['institucion'] ?? null,
                        ]
                    );

                    // Asociar especialidad
                    if (!$provider->specialties()->where('specialties.id', $specialty->id)->exists()) {
                        $provider->specialties()->attach($specialty->id);
                    }

                    $count++;
                }
            }
        }

        $this->command->info("  - {$fileName}: {$count} providers importados");
    }

    /**
     * Importar providers simples (sin especialidades) - para farmacias y vacunatorios
     * Estructura: {localidad: [providers]}
     */
    private function importProvidersSimple(string $fileName, string $type): void
    {
        $filePath = $this->jsonBasePath . '/' . $fileName;
        if (!File::exists($filePath)) {
            $this->command->warn("Archivo no encontrado: {$filePath}");
            return;
        }

        $data = json_decode(File::get($filePath), true);
        $count = 0;

        foreach ($data as $localidadName => $providers) {
            $localidad = Localidad::where('nombre', $localidadName)->first();
            if (!$localidad) {
                $this->command->warn("Localidad no encontrada: {$localidadName}");
                continue;
            }

            // Si providers es un string (mensaje de error), saltarlo
            if (is_string($providers)) {
                continue;
            }

            foreach ($providers as $providerData) {
                // Crear o obtener provider (sin especialidades)
                $provider = Provider::firstOrCreate(
                    [
                        'nombre' => $providerData['nombre'],
                        'direccion' => $providerData['direccion'],
                        'localidad_id' => $localidad->id,
                        'tipo' => $type,
                    ],
                    [
                        'telefono' => $providerData['telefono'] ?? null,
                        'institucion' => $providerData['institucion'] ?? null,
                    ]
                );

                $count++;
            }
        }

        $this->command->info("  - {$fileName}: {$count} providers importados");
    }

    /**
     * Importar profesionales desde professionals.json
     */
    private function importProfessionals(): void
    {
        $filePath = $this->jsonBasePath . '/professionals.json';
        if (!File::exists($filePath)) {
            $this->command->warn("Archivo no encontrado: {$filePath}");
            return;
        }

        $professionals = json_decode(File::get($filePath), true);
        $count = 0;

        foreach ($professionals as $professionalData) {
            $localidad = Localidad::where('nombre', $professionalData['localidad'])->first();
            if (!$localidad) {
                $this->command->warn("Localidad no encontrada: {$professionalData['localidad']}");
                continue;
            }

            $specialty = Specialty::where('nombre', $professionalData['especialidad'])
                ->where('tipo', 'medic')
                ->first();

            if (!$specialty) {
                $specialty = Specialty::create([
                    'nombre' => $professionalData['especialidad'],
                    'tipo' => 'medic',
                ]);
            }

            // Crear o obtener provider
            $provider = Provider::firstOrCreate(
                [
                    'nombre' => $professionalData['nombre'],
                    'direccion' => $professionalData['direccion'],
                    'localidad_id' => $localidad->id,
                    'tipo' => 'medic',
                ],
                [
                    'telefono' => $professionalData['telefono'] ?? null,
                    'institucion' => $professionalData['institucion'] ?? null,
                ]
            );

            // Asociar especialidad
            if (!$provider->specialties()->where('specialties.id', $specialty->id)->exists()) {
                $provider->specialties()->attach($specialty->id);
            }

            $count++;
        }

        $this->command->info("  - professionals.json: {$count} profesionales importados");
    }
}
