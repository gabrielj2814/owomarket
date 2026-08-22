<?php

namespace Src\Shared\Helper;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success($data = null, $message = 'Operación exitosa', $code = 200, $meta = null): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $code);
    }

    public static function created($data = null, $message = 'Recurso creado exitosamente', $meta = null): JsonResponse
    {
        return self::success($data, $message, 201, $meta);
    }

    /**
     * Respuesta paginada. **Este es el único formato de paginación de la API** (hallazgo N37).
     *
     *   { status, code, message, data: [...], pagination: { total, current_page, per_page, last_page }, meta }
     *
     * Antes convivían SEIS formas distintas en el cable, y cada página del frontend estaba
     * escrita contra la suya:
     *
     *   1. `data` plano con `pagination` en la raíz          — 10 endpoints (esta)
     *   2. `data: { data, pagination }`                      — facturas, clientes, pedidos
     *   3. `data: { data, total, per_page, ... }`            — reseñas, envíos
     *   4. `data` plano con `meta` en la raíz, sin `per_page` — pedidos del portal
     *   5. `data: { products, total, ... }`                  — marketplace central
     *   6. `success: true` con `meta`, sin `status` ni `code` — historial de tasas
     *
     * Se elige ésta porque ya era la mayoritaria, porque `data` sigue significando «el
     * payload» igual que en las respuestas sin paginar, y porque el tipo `Data<T>` del
     * frontend ya declaraba `pagination?`.
     *
     * **Los cuatro valores van como parámetros y no como un array suelto a propósito.**
     * Recibir `array $pagination` fue justamente lo que dejó que cada controlador
     * inventara sus propias claves. Así, el formato no se puede torcer sin cambiar esta
     * firma.
     */
    public static function paginated(
        array $data,
        int $total,
        int $currentPage,
        int $perPage,
        int $lastPage,
        string $message = 'Operación exitosa',
        int $code = 200,
        $meta = null
    ): JsonResponse {
        return new JsonResponse([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'pagination' => [
                'total' => $total,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ],
        ], $code);
    }

    public static function error($message = 'Error', $code = 400, $errors = null): JsonResponse
    {
        return new JsonResponse([
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'data' => [],
            'errors' => $errors,
        ], $code);
    }
}
