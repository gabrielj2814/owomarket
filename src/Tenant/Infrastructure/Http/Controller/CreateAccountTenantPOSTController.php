<?php


namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\AssignTenantToUserUseCase;
use Src\Tenant\Application\UseCase\CreateTenantOwonerUseCase;
use Src\Tenant\Application\UseCase\CreateTenantUseCase;
use Src\Tenant\Application\UseCase\DeleteTenantByUuidUseCase;
use Src\Tenant\Application\UseCase\DeleteTenantOwnerByUuidUseCase;
use Src\Tenant\Application\UseCase\DeleteTenantUserByUuidUseCase;
use Src\Tenant\Application\UseCase\ForceDeletedTenantByUuidUseCase;
use Src\Tenant\Application\UseCase\ForceDeletedTenantOwnerByUuidUseCase;
use Src\Tenant\Domain\ValuesObjects\RoleTenantUser;
use Src\Tenant\Infrastructure\Http\Request\CreateTenantOwnerAccountFormRequest;

class CreateAccountTenantPOSTController extends Controller {


    public function __construct(
        protected CreateTenantOwonerUseCase             $createTenantOwnerUseCase,
        protected CreateTenantUseCase                   $createTenantUseCase,
        protected AssignTenantToUserUseCase             $assignTenantToUserUseCase,
        protected DeleteTenantOwnerByUuidUseCase        $deleteTenantOwnerByUuidUseCase,
        protected ForceDeletedTenantOwnerByUuidUseCase  $forceDeletedTenantOwnerByUuidUseCase,
        protected DeleteTenantByUuidUseCase             $deleteTenantByUuidUseCase,
        protected ForceDeletedTenantByUuidUseCase       $forceDeletedTenantByUuidUseCase,
        protected DeleteTenantUserByUuidUseCase         $deleteTenantUserByUuidUseCase
    ) {}



    public function index(CreateTenantOwnerAccountFormRequest $request ){

        // data json de ejemplo
        // {
        //     "name": "Jaen Doe",
        //     "email": "jaen@example.com",
        //     "phone": "12345678901",
        //     "password": "securepassword",
        //     "tenant_name": "Acme Corp"
        // }

        $owner=null;
        $tenant=null;
        $tenantUser=null;
        $data=$request->data;

        try{

            $owner=$this->createTenantOwnerUseCase->execute(
                $data->name,
                $data->email,
                $data->phone,
                $data->password
            );

            $tenant=$this->createTenantUseCase->execute(
                $data->store_name
            );

            $tenantUser=$this->assignTenantToUserUseCase->execute(
                tenantId: $tenant->getId()->value(),
                userId: $owner->getId()->value(),
                role: RoleTenantUser::owner(),
                permissions: null
            );

            return ApiResponse::success(
                null,
                "Cuenta de tenant creada exitosamente",
                201
            );
        }
        catch (\Exception $e){
            Log::info('Error al crear la cuenta del tenant: '.$e->getMessage());

            if($owner!==null){
                $this->deleteTenantOwnerByUuidUseCase->execute($owner->getId()->value());
                $this->forceDeletedTenantOwnerByUuidUseCase->execute($owner->getId()->value());
            }

            if($tenant!==null){
                $this->deleteTenantByUuidUseCase->execute($tenant->getId()->value());
                $this->forceDeletedTenantByUuidUseCase->execute($tenant->getId()->value());
            }

            if($tenantUser!==null){
                $this->deleteTenantUserByUuidUseCase->execute($tenantUser->getId()->value());
            }

            return ApiResponse::error(
                "Error al crear la cuenta del tenant",
                500,
                [ 'error' => $e->getMessage() ]
            );
        }

    }



}



?>
