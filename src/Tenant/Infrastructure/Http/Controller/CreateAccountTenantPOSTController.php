<?php


namespace Src\Tenant\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Application\UseCase\CreateTenantOwonerUseCase;
use Src\Tenant\Application\UseCase\CreateTenantUseCase;
use Src\Tenant\Application\UseCase\DeleteTenantByUuidUseCase;
use Src\Tenant\Application\UseCase\DeleteTenantOwnerByUuidUseCase;
use Src\Tenant\Application\UseCase\ForceDeletedTenantByUuidUseCase;
use Src\Tenant\Application\UseCase\ForceDeletedTenantOwnerByUuidUseCase;
use Src\Tenant\Infrastructure\Http\Request\CreateTenantOwnerAccountFormRequest;

class CreateAccountTenantPOSTController extends Controller {


    public function __construct(
        protected CreateTenantOwonerUseCase $createTenantOwnerUseCase,
        protected CreateTenantUseCase       $createTenantUseCase,
        protected DeleteTenantOwnerByUuidUseCase $deleteTenantOwnerByUuidUseCase,
        protected ForceDeletedTenantOwnerByUuidUseCase $forceDeletedTenantOwnerByUuidUseCase,
        protected DeleteTenantByUuidUseCase $deleteTenantByUuidUseCase,
        protected ForceDeletedTenantByUuidUseCase  $forceDeletedTenantByUuidUseCase
    ) {}



    public function index(CreateTenantOwnerAccountFormRequest $request ){

        // Assign tenant to tenant owner
        // crear el VO de RoleTenantUser para poder asignar roles futuros
        // crear entidad que represente la relacion tenant-owner
        // persistir la relacion


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
        $data=$request->data;

        try{

            $owner=$this->createTenantOwnerUseCase->execute(
                $data->name,
                $data->email,
                $data->phone,
                $data->password
            );

            $tenant=$this->createTenantUseCase->execute(
                $data->tenant_name
            );

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

            if($owner!==null){
                $this->deleteTenantOwnerByUuidUseCase->execute($owner->getId()->value());
                $this->forceDeletedTenantOwnerByUuidUseCase->execute($owner->getId()->value());
            }

            if($tenant!==null){
                $this->deleteTenantByUuidUseCase->execute($tenant->getId()->value());
                $this->forceDeletedTenantByUuidUseCase->execute($tenant->getId()->value());
            }

            return ApiResponse::error(
                "Error al crear la cuenta del tenant",
                500,
                $e->getMessage()
            );
        }

    }



}



?>
