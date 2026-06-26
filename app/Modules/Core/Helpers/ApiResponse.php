<?php

namespace App\Modules\Core\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse {


    /**
     * Devuelve una respuesta JSON exitosa.
     *
     * @param mixed $data Los datos a retornar.
     * @param string $message El mensaje de éxito.
     * @param int $code El código de estado HTTP.
     * @param mixed $meta Metadatos adicionales opcionales.
     * @return JsonResponse
     */
    public static function success($data = null, $message = 'Operación exitosa', $code = 200, $meta = null): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ],$code);
    }

    /**
     * Devuelve una respuesta JSON de error.
     *
     * @param string $message El mensaje de error.
     * @param int $code El código de estado HTTP.
     * @param mixed $errors Detalles adicionales de los errores.
     * @return JsonResponse
     */
    public static function error($message = 'Error', $code = 400, $errors = null): JsonResponse
    {

        return new JsonResponse([
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'data' => [
                'errors' => $errors
            ]
        ],$code);
    }
}





?>
