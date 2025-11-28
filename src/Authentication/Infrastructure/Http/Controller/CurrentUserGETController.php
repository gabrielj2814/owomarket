<?php


namespace Src\Authentication\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Src\Shared\Helper\ApiResponse;

class CurrentUserGETController extends Controller {


    public function index():JsonResponse {
        $currentUser = Auth::user(); // <- esto mejorarlo con una tabal llamanda auth_users para no depender de la tabla users directamente
        // Log::info(__METHOD__." Current User => ".json_encode($currentUser));
        return ApiResponse::success(
            data: [
                'user' => $currentUser
            ],
            message: 'Usuario actual obtenido con éxito',
            code: 200
        );
    }


}



?>
