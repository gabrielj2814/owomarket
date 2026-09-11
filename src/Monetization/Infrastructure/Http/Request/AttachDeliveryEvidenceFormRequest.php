<?php

declare(strict_types=1);

namespace Src\Monetization\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validacion de las evidencias de entrega, del comerciante y del comprador (subsistema 3).
 *
 * Esto es frontera de confianza --entra un fichero de fuera-- asi que aqui no se recorta
 * nada: tipo comprobado, tamaño acotado y numero de ficheros limitado. Sin el tope de
 * cantidad, una sola peticion con doscientos videos llena el disco del servidor.
 *
 * Los 50MB por fichero son los mismos que ya aplica `UploadSupportAttachmentService`, que es
 * quien los guarda. Dos limites distintos para lo mismo son un limite que no se aplica.
 */
final class AttachDeliveryEvidenceFormRequest extends FormRequest
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
            'evidence' => ['required', 'array', 'min:1', 'max:10'],
            'evidence.*' => ['required', 'file', 'max:51200', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'evidence.required' => 'Adjunta al menos una foto o un video.',
            'evidence.max' => 'Puedes adjuntar como máximo 10 archivos por vez.',
            'evidence.*.max' => 'Cada archivo debe pesar menos de 50MB.',
            'evidence.*.mimes' => 'Solo se aceptan imágenes (jpg, png, webp, gif) o videos (mp4, webm, mov).',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'code' => 422,
            'status' => 'error',
            'message' => 'Las evidencias adjuntas no son válidas',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
