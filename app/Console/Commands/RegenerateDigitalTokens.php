<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class RegenerateDigitalTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:regenerate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera los tokens digitales de 3 dígitos para usuarios con sesión activa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Obtener todos los usuarios con token activo
        $users = User::where('digital_token_active', true)->get();
        
        $count = 0;
        foreach ($users as $user) {
            $user->regenerateDigitalToken();
            $count++;
        }
        
        if ($count > 0) {
            $this->info("Se regeneraron {$count} token(s) digital(es).");
        } else {
            // No mostrar nada si no hay usuarios activos para evitar spam en logs
            $this->line("No hay usuarios con token activo.");
        }
        
        return Command::SUCCESS;
    }
}
