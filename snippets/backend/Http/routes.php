<?php

/*
 * Rate limiting de los formularios públicos.
 * Decisión: middleware `throttle` por ruta, solo en los POST públicos, con
 * límites distintos según el riesgo de abuso de cada formulario. La ruta de
 * verificación restringe el parámetro a números antes de llegar al controlador.
 */

// routes/web.php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PrivacyRequestController;
use Illuminate\Support\Facades\Route;

// ...

Route::get('/agendar', [AppointmentController::class, 'create'])->name('agendar.create');
Route::post('/agendar', [AppointmentController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('agendar.store');

// ...

Route::get('/privacidad/solicitud', [PrivacyRequestController::class, 'create'])->name('privacy.create');
Route::post('/privacidad/solicitud', [PrivacyRequestController::class, 'store'])
    ->middleware('throttle:3,1')
    ->name('privacy.store');
Route::get('/privacidad/verificar/{privacyRequest}', [PrivacyRequestController::class, 'verify'])
    ->whereNumber('privacyRequest')
    ->name('privacy.verify');
