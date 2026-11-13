<?php

/*
 * Modelo de solicitud de asesoría con datos personales.
 * Decisión: cifrado en reposo con el cast `encrypted` para los campos
 * sensibles, asignación masiva limitada con `$fillable` y retención máxima
 * de 12 meses mediante el trait Prunable.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class Appointment extends Model
{
    use HasFactory, Prunable;

    protected $fillable = [
        'name',
        'email',
        'request_type',
        'phone',
        'region',
        'comuna',
        'address',
        'status',
        'notes',
        'consented_at',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        // Cifrado en reposo (Ley 21.719). name y email quedan en texto plano para búsquedas.
        'phone' => 'encrypted',
        'region' => 'encrypted',
        'comuna' => 'encrypted',
        'address' => 'encrypted',
        'notes' => 'encrypted',
    ];

    /**
     * Retención máxima de 12 meses (Ley 21.719). Lo ejecuta `model:prune` a diario.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subMonths(12));
    }
}
