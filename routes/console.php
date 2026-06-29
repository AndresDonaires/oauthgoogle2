<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use App\Models\Sesion;
use App\Models\Usuario;
use App\Mail\RecordatorioSesion;
use Carbon\Carbon;

Artisan::command('sesiones:enviar-recordatorios', function () {
    $hoy = \Carbon\Carbon::now()->format('Y-m-d');
    $tresDiasDespues = \Carbon\Carbon::now()->addDays(3)->format('Y-m-d');

    $sesiones = \App\Models\Sesion::with(['mentor', 'aprendiz'])
                      ->whereBetween('fecha', [$hoy, $tresDiasDespues])
                      ->where('estado', 'confirmada')
                      ->get();

    if ($sesiones->isEmpty()) {
        $this->comment('No se encontraron sesiones para enviar recordatorios.');
        return;
    }

    foreach ($sesiones as $sesion) {
        // 1. Validar integridad de datos
        if (!$sesion->mentor || !$sesion->aprendiz) {
            $this->warn("Sesión ID {$sesion->id} omitida por falta de datos.");
            continue;
        }

        // 2. Intentar envío con seguridad
        try {
            \Illuminate\Support\Facades\Mail::to($sesion->mentor->email)
                ->cc($sesion->aprendiz->email)
                ->send(new \App\Mail\RecordatorioSesion($sesion));
                
            $this->info("Enviado ID {$sesion->id}. Destinatario: {$sesion->mentor->email}, CC: {$sesion->aprendiz->email}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error al enviar sesión {$sesion->id}: " . $e->getMessage());
            $this->error("Fallo al enviar sesión ID: {$sesion->id}. Revisa el log de errores.");
        }
    }
})->purpose('Envía correos de recordatorio');