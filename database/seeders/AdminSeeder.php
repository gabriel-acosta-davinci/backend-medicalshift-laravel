<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * Este seeder crea o actualiza únicamente el usuario administrador
     * sin afectar los demás datos de la base de datos.
     */
    public function run(): void
    {
        $this->command->info('Creando/actualizando usuario administrador...');

        // Buscar si ya existe un usuario con este email
        $admin = User::where('email', 'admin@medicalshift.com')->first();

        if ($admin) {
            // Si existe, actualizarlo para asegurar que sea admin
            $admin->update([
                'name' => 'Administrador',
                'name' => 'Administrador',
                'document_number' => $admin->document_number ?? '42435290',
                'password' => Hash::make('Admin123!'),
                'is_admin' => true,
                'password_updated_at' => now(),
            ]);
            $this->command->info("Usuario administrador actualizado: {$admin->email}");
        } else {
            // Si no existe, crearlo
            $admin = User::create([
                'name' => 'Administrador',
                'name' => 'Administrador',
                'email' => 'admin@medicalshift.com',
                'document_number' => '42435290',
                'password' => Hash::make('Admin123!'),
                'is_admin' => true,
                'password_updated_at' => now(),
                'email_verified_at' => now(),
            ]);
            $this->command->info("Usuario administrador creado: {$admin->email}");
        }

        $this->command->info('¡Usuario administrador listo!');
        $this->command->info('Credenciales:');
        $this->command->info('  Email: admin@medicalshift.com');
        $this->command->info('  Contraseña: Admin123!');
    }
}

