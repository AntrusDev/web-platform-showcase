<?php

/*
 * Pruebas Feature del formulario público de asesorías.
 * Decisión: se prueba a través de la ruta HTTP real (no la regla aislada),
 * con regiones y comunas sembradas en la BD, cubriendo entradas manipuladas
 * y el rate limit, y verificando que no quede ningún registro guardado.
 */

namespace Tests\Feature;

use Database\Seeders\GeoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GeoSeeder::class);
    }

    private function validData(): array
    {
        return [
            'name' => 'Test User',
            'email' => 'cliente@example.com',
            'phone' => '+56900000000',
            'region' => 'La Araucanía',
            'comuna' => 'Temuco',
            'address' => 'Calle Ejemplo 123',
            'request_type' => 'service_a',
            'privacy_consent' => true,
        ];
    }

    // ...

    public function test_appointment_is_rejected_when_the_commune_belongs_to_another_region(): void
    {
        // Concepción existe, pero pertenece a Biobío
        $data = [...$this->validData(), 'comuna' => 'Concepción'];

        $this->from('/agendar')
            ->post('/agendar', $data)
            ->assertSessionHasErrors('comuna')
            ->assertSessionDoesntHaveErrors('region')
            ->assertRedirect('/agendar');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_appointment_is_rejected_with_a_region_outside_the_service_area(): void
    {
        // Atacama exists in the database but is not offered in the form
        $data = [...$this->validData(), 'region' => 'Atacama', 'comuna' => 'Copiapó'];

        $this->from('/agendar')
            ->post('/agendar', $data)
            ->assertSessionHasErrors(['region', 'comuna'])
            ->assertRedirect('/agendar');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_appointment_submissions_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/agendar', $this->validData())->assertRedirect();
        }

        $this->post('/agendar', $this->validData())->assertStatus(429);

        $this->assertDatabaseCount('appointments', 5);
    }
}
