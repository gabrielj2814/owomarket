<?php

namespace Src\Shared\Helper;

use Illuminate\Http\JsonResponse;

class ApiResponse {


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

    public static function Pagination($data = null, $message = 'Operación exitosa', $code = 200, $meta = null, $pagination = null): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'pagination' => $pagination,
        ],$code);
    }

    public static function error($message = 'Error', $code = 400, $errors = null): JsonResponse
    {

        return new JsonResponse([
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'data' => [],
            'errors' => $errors
        ],$code);
    }
}





?>
