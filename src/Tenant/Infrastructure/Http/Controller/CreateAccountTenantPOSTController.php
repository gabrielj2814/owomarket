<?php


namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\CreateTenantOwonerUseCase;
use Src\Tenant\Application\UseCase\CreateTenantUseCase;
use Src\Tenant\Infrastructure\Http\Request\CreateTenantOwnerAccountFormRequest;

class CreateAccountTenantPOSTController extends Controller {


    public function __construct(
        protected CreateTenantOwonerUseCase $createTenantOwnerUseCase,
        protected CreateTenantUseCase       $createTenantUseCase
    ) {}



    public function index(CreateTenantOwnerAccountFormRequest $request ){

        // TODO: steps
        // 1. Create tenant owner OK
        // 2. Create tenant OK
        // 3. Assign tenant to tenant owner
        // 4. crear domain para el tenant
        // 5. crear DB para el tenant


        // data json de ejemplo
        // {
        //     "name": "John Doe",
        //     "email": "john@example.com",
        //     "phone": "+1234567890",
        //     "password": "securepassword",
        //     "tenant_name": "Acme Corp"
        // }

        $data=$request->data;
        try{
            // DB::beginTransaction();

            $owner=$this->createTenantOwnerUseCase->execute(
                $data->name,
                $data->email,
                $data->phone,
                $data->password
            );

            $tenant=$this->createTenantUseCase->execute(
                $data->tenant_name
            );

            // DB::commit();

            return ApiResponse::success(
                [
                    'owner'=>$owner,
                    'tenant'=>$tenant
                ],
                "Cuenta de tenant creada exitosamente",
                201
            );
        }
        catch (\Exception $e){
            // DB::rollback();

            return ApiResponse::error(
                "Error al crear la cuenta del tenant",
                500,
                $e->getMessage()
            );
        }
    }



}



?>
