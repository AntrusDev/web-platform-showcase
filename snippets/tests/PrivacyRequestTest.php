<?php

/*
 * Pruebas Feature del flujo ARCOP: enlace firmado y autorización del panel.
 * Decisión: se prueban los ataques concretos (firma reutilizada en otra
 * solicitud, enlace sin firma, enlace vencido) y que un usuario autenticado
 * pero sin permiso reciba 403, no solo que un invitado sea redirigido.
 */

namespace Tests\Feature;

use App\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PrivacyRequestTest extends TestCase
{
    use RefreshDatabase;

    // ... validData(), createRequest() y signedUrl()

    public function test_tampered_or_expired_links_are_rejected(): void
    {
        $privacyRequest = $this->createRequest();
        $other = $this->createRequest();

        // Firma de una solicitud usada para otra
        $tampered = str_replace('/verificar/'.$privacyRequest->id, '/verificar/'.$other->id, $this->signedUrl($privacyRequest));
        $this->get($tampered)->assertForbidden();
        $this->get('/privacidad/verificar/'.$privacyRequest->id)->assertForbidden();

        $expired = URL::temporarySignedRoute('privacy.verify', now()->subMinute(), ['privacyRequest' => $privacyRequest->id]);
        $this->get($expired)->assertForbidden();

        $this->assertSame(PrivacyRequest::STATUS_PENDING_VERIFICATION, $privacyRequest->fresh()->status);
        $this->assertSame(PrivacyRequest::STATUS_PENDING_VERIFICATION, $other->fresh()->status);
    }

    public function test_reopening_the_link_does_not_roll_back_a_resolved_request(): void
    {
        $privacyRequest = $this->createRequest(PrivacyRequest::STATUS_RESOLVED);

        $this->get($this->signedUrl($privacyRequest))->assertRedirect('/privacidad/solicitud');

        $this->assertSame(PrivacyRequest::STATUS_RESOLVED, $privacyRequest->fresh()->status);
        $this->assertNull($privacyRequest->fresh()->verified_at);
    }

    public function test_unauthorized_users_cannot_access_requests_in_the_panel(): void
    {
        $privacyRequest = $this->createRequest();

        $this->get('/admin/privacy-requests')->assertRedirect('/admin/login');

        $this->actingAs(User::factory()->create());
        $this->get('/admin/privacy-requests')->assertForbidden();
        $this->get('/admin/privacy-requests/'.$privacyRequest->id.'/edit')->assertForbidden();
    }
}
