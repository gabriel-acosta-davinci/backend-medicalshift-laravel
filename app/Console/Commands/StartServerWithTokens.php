<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class StartServerWithTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve:with-tokens 
                            {--host=127.0.0.1 : The host address to serve the application on}
                            {--port=8000 : The port to serve the application on}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inicia el servidor de desarrollo junto con el regenerador de tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = $this->option('host');
        $port = $this->option('port');

        $this->info('========================================');
        $this->info('Iniciando servidor Laravel con regenerador de tokens');
        $this->info('========================================');
        $this->newLine();
        $this->info("Servidor: http://{$host}:{$port}");
        $this->info('Regenerador de tokens: cada 30 segundos');
        $this->newLine();
        $this->warn('Presiona Ctrl+C para detener ambos procesos');
        $this->newLine();

        // Ruta al script del daemon
        $daemonPath = base_path('regenerate-tokens-daemon.php');
        
        // Verificar que el archivo existe
        if (!file_exists($daemonPath)) {
            $this->error("No se encontró el archivo regenerate-tokens-daemon.php");
            return Command::FAILURE;
        }

        // Determinar el comando según el sistema operativo
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if ($isWindows) {
            // Windows: usar start para ejecutar en segundo plano
            $daemonCommand = "start \"Token Regenerator\" cmd /c \"php {$daemonPath}\"";
            $this->info('Iniciando regenerador de tokens en segundo plano...');
            exec($daemonCommand);
            sleep(2); // Esperar a que inicie
        } else {
            // Linux/Mac: usar nohup o ejecutar en segundo plano
            $this->info('Iniciando regenerador de tokens en segundo plano...');
            $process = new Process(['php', $daemonPath]);
            $process->setTimeout(null);
            $process->start();
            
            // Guardar el PID para poder detenerlo después
            $pid = $process->getPid();
            $this->info("Regenerador iniciado con PID: {$pid}");
        }

        $this->info('Iniciando servidor Laravel...');
        $this->newLine();

        // Ejecutar el comando serve usando Artisan::call
        // Esto ejecutará el comando serve en el mismo proceso
        try {
            $this->call('serve', [
                '--host' => $host,
                '--port' => $port,
            ]);
        } catch (\Exception $e) {
            // Si hay un error, detener el daemon
            $this->error('Error al iniciar el servidor: ' . $e->getMessage());
            
            if ($isWindows) {
                exec('taskkill /FI "WINDOWTITLE eq Token Regenerator*" /T /F >nul 2>&1');
            }
            
            return Command::FAILURE;
        }

        // Si llegamos aquí, el servidor se detuvo (Ctrl+C)
        $this->newLine();
        $this->info('Deteniendo regenerador de tokens...');
        
        if ($isWindows) {
            // En Windows, intentar detener el proceso
            exec('taskkill /FI "WINDOWTITLE eq Token Regenerator*" /T /F >nul 2>&1');
        } else {
            // En Linux/Mac, el proceso debería terminar automáticamente
            // pero si guardamos el PID, podríamos matarlo aquí
        }

        $this->info('Servidor y regenerador detenidos.');
        
        return Command::SUCCESS;
    }
}
