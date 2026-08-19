<?php

declare(strict_types=1);

namespace Src\TenantSettings\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\TenantSettings\Application\DTOs\SaveSettingData;

final class SaveSettingFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            'value' => ['nullable'],
            'type' => ['nullable', 'string', 'in:string,boolean,json,integer,float'],
            'group' => ['nullable', 'string', 'in:general,appearance,social,seo,notifications'],
        ];
    }

    public function toDto(): SaveSettingData
    {
        $validated = $this->validated();

        return new SaveSettingData(
            key: (string) $validated['key'],
            value: isset($validated['value']) ? (string) $validated['value'] : null,
            type: (string) ($validated['type'] ?? 'string'),
            group: (string) ($validated['group'] ?? 'general')
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 422,
            'message' => 'Errores de validación en parámetro individual de configuración.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
