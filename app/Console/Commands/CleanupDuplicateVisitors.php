<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppointmentVisitor;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateVisitors extends Command
{
    protected $signature = 'visitors:cleanup-duplicates';
    protected $description = 'Unifica visitantes duplicados por email conservando los datos adicionales en observaciones.';

    public function handle()
    {
        // Encontrar emails duplicados
        $duplicateEmails = AppointmentVisitor::select('email')
            ->whereNotNull('email')
            ->groupBy('email')
            ->havingRaw('COUNT(id) > 1')
            ->pluck('email');

        if ($duplicateEmails->isEmpty()) {
            $this->info('No se encontraron visitantes duplicados.');
            return 0;
        }

        $this->info('Encontrados ' . $duplicateEmails->count() . ' emails duplicados. Iniciando unificación...');

        DB::beginTransaction();
        try {
            foreach ($duplicateEmails as $email) {
                // Obtener todos los visitantes con este email, ordenados por ID (el más antiguo primero)
                $visitors = AppointmentVisitor::where('email', $email)->orderBy('id', 'asc')->get();
                
                // El primero será el principal, el dueño legítimo del correo según el histórico
                $primaryVisitor = $visitors->shift();
                
                $this->info("Procesando {$email}: Principal ID {$primaryVisitor->id} ({$primaryVisitor->first_name} {$primaryVisitor->last_name})");

                $additionalInfo = [];

                foreach ($visitors as $duplicate) {
                    // Recopilar información del duplicado para no perderla
                    $info = "Antiguo registro fusionado (ID: {$duplicate->id}): "
                          . "Nombre: {$duplicate->first_name} {$duplicate->last_name}";
                    if ($duplicate->dni) $info .= ", DNI: {$duplicate->dni}";
                    if ($duplicate->phone) $info .= ", Tel: {$duplicate->phone}";
                    if ($duplicate->city) $info .= ", Ciudad: {$duplicate->city}";
                    if ($duplicate->observations) $info .= ", Obs originales: {$duplicate->observations}";
                    
                    $additionalInfo[] = $info;

                    // Mover citas del duplicado al principal
                    Appointment::where('visitor_id', $duplicate->id)
                        ->update(['visitor_id' => $primaryVisitor->id]);

                    // Eliminar el visitante duplicado
                    $duplicate->delete();
                }

                // Guardar la información adicional en las observaciones del visitante principal
                if (!empty($additionalInfo)) {
                    $newObservations = $primaryVisitor->observations 
                        ? $primaryVisitor->observations . "\n\n--- Fusiones Automáticas ---\n" 
                        : "--- Fusiones Automáticas ---\n";
                    
                    $newObservations .= implode("\n", $additionalInfo);
                    
                    $primaryVisitor->observations = $newObservations;
                    $primaryVisitor->save();
                }
            }
            
            DB::commit();
            $this->info('Unificación completada con éxito.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error durante la unificación: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
