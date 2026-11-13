<?php

/*
 * Validación del formulario público de solicitud de asesoría.
 * Decisión: el <select> del frontend es solo UX; la región se valida contra
 * la zona de cobertura y la comuna debe pertenecer a la región enviada,
 * así una petición manipulada (curl, Postman, HTML editado) se rechaza.
 */

namespace App\Http\Requests;

use App\Models\Region;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'region' => [
                'required',
                'string',
                'max:255',
                Rule::exists('regions', 'name')->whereIn('name', Region::SCHEDULING_REGIONS),
            ],
            // The commune must belong to the submitted region; an invalid region resolves to null and fails here too
            'comuna' => [
                'required',
                'string',
                'max:255',
                Rule::exists('communes', 'name')->where('region_id', $this->schedulingRegionId()),
            ],
            'address' => 'required|string|max:255',
            'request_type' => 'required|string|in:service_a,service_b',
            'privacy_consent' => 'accepted',
        ];
    }

    /**
     * ID of the submitted region when it is within the service area, null otherwise.
     */
    private function schedulingRegionId(): ?int
    {
        $region = $this->input('region');

        if (! is_string($region) || ! in_array($region, Region::SCHEDULING_REGIONS, true)) {
            return null;
        }

        return Region::where('name', $region)->value('id');
    }
}
