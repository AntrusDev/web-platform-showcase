<?php

/*
 * Flujo ARCOP: el titular confirma su identidad con un enlace firmado.
 * Decisión: URL firmada temporal (2 horas) en vez de tokens propios en BD,
 * respuesta idéntica exista o no el correo (evita enumeración) y un estado
 * que solo avanza, para que reabrir el enlace no lo haga retroceder.
 */

namespace App\Http\Controllers;

use App\Http\Requests\StorePrivacyRequestRequest;
use App\Mail\PrivacyVerificationMail;
use App\Models\PrivacyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

class PrivacyRequestController extends Controller
{
    // ...

    public function store(StorePrivacyRequestRequest $request)
    {
        $privacyRequest = PrivacyRequest::create([
            ...$request->validated(),
            'status' => PrivacyRequest::STATUS_PENDING_VERIFICATION,
        ]);

        $url = URL::temporarySignedRoute(
            'privacy.verify',
            now()->addHours(2),
            ['privacyRequest' => $privacyRequest->id],
        );

        try {
            Mail::to($privacyRequest->email)->send(new PrivacyVerificationMail($url));
        } catch (Throwable $e) {
            // El mensaje de la excepción puede incluir el correo del destinatario: solo se registra el id
            Log::error('No se pudo enviar el correo de verificación ARCOP.', [
                'privacy_request_id' => $privacyRequest->id,
                'exception' => $e::class,
            ]);
        }

        // Misma respuesta siempre, para no revelar si el correo existe en el sistema
        return back()->with('success', 'Hemos enviado un enlace de verificación a su correo. Por favor, haga clic en él para confirmar que es el titular de los datos.');
    }

    public function verify(Request $request, PrivacyRequest $privacyRequest)
    {
        abort_unless($request->hasValidSignature(), 403);

        // Solo avanza desde pending_verification: volver a abrir el enlace no retrocede el estado
        if ($privacyRequest->status === PrivacyRequest::STATUS_PENDING_VERIFICATION) {
            $privacyRequest->update([
                'status' => PrivacyRequest::STATUS_PENDING_PROCESSING,
                'verified_at' => now(),
            ]);
        }

        return redirect()
            ->route('privacy.create')
            ->with('success', 'Identidad verificada. Procesaremos su solicitud.');
    }
}
