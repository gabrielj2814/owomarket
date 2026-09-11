<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validacion del expediente de identidad (subsistema 1).
 *
 * Frontera de confianza y ademas datos que acaban cifrados en reposo, asi que aqui no se
 * recorta: si entra basura, el administrador revisa basura y la verificacion no verifica nada.
 *
 * La cedula se valida por FORMATO y no por existencia: la plataforma no tiene acceso a ningun
 * registro civil. Lo que garantiza esta regla es que el dato sea utilizable en una reclamacion,
 * no que sea cierto -- de eso responde la revision humana.
 */
final class SubmitTenantKycFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'min:3', 'max:150'],
            // Entre 6 y 9 digitos, con o sin separadores: el guion y los puntos se admiten
            // porque es como la gente los escribe, y el modelo los normaliza al calcular el
            // hash.
            'cedula' => ['required', 'string', 'regex:/^[VEve]?[-\s]?[\d.\s]{6,12}$/'],
            'nationality' => ['nullable', 'string', 'in:V,E,v,e'],
            // El RIF es opcional: un comerciante persona natural puede no tenerlo.
            'rif' => ['nullable', 'string', 'regex:/^[JGVEPjgvep][-\s]?[\d.\s-]{8,12}$/'],
            'phone' => ['required', 'string', 'min:7', 'max:25'],
            'address' => ['required', 'string', 'min:10', 'max:500'],
            'document' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'legal_name.required' => 'Escribe tu nombre tal como aparece en tu cédula.',
            'cedula.required' => 'La cédula es obligatoria.',
            'cedula.regex' => 'La cédula no tiene un formato válido. Ejemplo: V-12345678.',
            'rif.regex' => 'El RIF no tiene un formato válido. Ejemplo: J-401234567.',
            'address.required' => 'La dirección es obligatoria.',
            'address.min' => 'Escribe una dirección completa: es a donde llegaría una notificación.',
            'document.max' => 'El archivo debe pesar menos de 10MB.',
            'document.mimes' => 'Adjunta una imagen (jpg, png, webp) o un PDF.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'code' => 422,
            'status' => 'error',
            'message' => 'Revisa los datos de verificación',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
