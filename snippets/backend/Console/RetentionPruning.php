<?php

/*
 * Purga automática de datos personales vencidos.
 * Decisión: en vez de un comando Artisan propio, cada modelo declara su
 * regla de retención con el trait Prunable y el scheduler ejecuta el
 * comando nativo `model:prune` una vez al día.
 */

// routes/console.php

use Illuminate\Support\Facades\Schedule;

// ...

// Purga diaria de datos vencidos (modelos con el trait Prunable)
Schedule::command('model:prune')->daily();

// app/Models/Appointment.php

class Appointment extends Model
{
    use HasFactory, Prunable;

    // ...

    /**
     * Retención máxima de 12 meses (Ley 21.719). Lo ejecuta `model:prune` a diario.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subMonths(12));
    }
}

// app/Models/PrivacyRequest.php

class PrivacyRequest extends Model
{
    use Prunable;

    // ...

    /**
     * Solicitudes nunca verificadas se eliminan a los 7 días.
     */
    public function prunable(): Builder
    {
        return static::where('status', self::STATUS_PENDING_VERIFICATION)
            ->where('created_at', '<=', now()->subDays(7));
    }
}
