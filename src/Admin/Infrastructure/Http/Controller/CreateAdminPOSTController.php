<?php

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Admin\Application\UseCase\CreateAdminUseCase;
use Src\Admin\Infrastructure\Http\Request\CreateAdminFormRequest;
use Src\Shared\Domain\Contracts\PasswordGenerator;
use Src\Shared\Helper\ApiResponse;

class CreateAdminPOSTController extends Controller
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected CreateAdminUseCase $create_admin_use_case,
        protected PasswordGenerator $password_generator
    ) {}

    /**
     * Método index.
     */
    public function index(CreateAdminFormRequest $request): JsonResponse
    {
        $data = $request->data;

        $name = $data->name;
        $email = $data->email;
        $phone = $data->phone;

        if (env('APP_ENV') == 'local') {
            $password = env('USER_PASSWORD_DEV');
        } else {
            $password = $this->password_generator->generate(12);
        }

        $admin = $this->create_admin_use_case->execute($name, $email, $phone, $password);

        $dataRespose = [
            'id' => $admin->getId()->value(),
            'name' => $admin->getName()->value(),
            'email' => $admin->getEmail()->value(),
            'type' => $admin->getType()->value(),
            'phone' => $admin->getPhone()->value(),
            'created_at' => $admin->getCreatedAt()->value()->format('Y-m-d'),
        ];

        return ApiResponse::success(data: $dataRespose, message: 'ok', code: 200);
    }
}
