<?php


namespace Src\Tenant\Infrastructure\Eloquent\Repositories;

use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Src\Shared\Collection\Collection;
use Src\Shared\Collection\Pagination;
use Src\Shared\ValuesObjects\CreatedAt;
use Src\Shared\ValuesObjects\Currency;
use Src\Shared\ValuesObjects\SoftDeleteAt;
use Src\Shared\ValuesObjects\Timezone;
use Src\Shared\ValuesObjects\UpdatedAt;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\Entities\Tenant;
use Src\Tenant\Domain\Entities\TenantOwner;
use Src\Tenant\Domain\ValuesObjects\AvatarUrl;
use Src\Tenant\Domain\ValuesObjects\PhoneNumber;
use Src\Tenant\Domain\ValuesObjects\Slug;
use Src\Tenant\Domain\ValuesObjects\TenantName;
use Src\Tenant\Domain\ValuesObjects\TenantRequest;
use Src\Tenant\Domain\ValuesObjects\TenantStatus;
use Src\Tenant\Domain\ValuesObjects\UserEmail;
use Src\Tenant\Domain\ValuesObjects\UserName;
use Src\Tenant\Domain\ValuesObjects\UserStatus;
use Src\Tenant\Domain\ValuesObjects\UserType;
use Src\Tenant\Domain\ValuesObjects\Uuid;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;

class TenantRepository implements TenantRepositoryInterface {



    public function filter(
        string | null $search,
        string | null $fechaDesdeUTC,
        string | null $fechaHastaUTC,
        string | null $status,
        string | null $request,
        int $prePage=50
    ): Pagination{
        $consulta= ModelsTenant::query();

        if($search!="" && $search!=null){
            $consulta->where(function($query) use ($search){
               $query->where("name","like","%".$search."%")
               ->orWhere("slug","like","%".$search."%");
            });
        }

        if(($fechaDesdeUTC!="" && $fechaDesdeUTC!=null) && ($fechaHastaUTC!="" && $fechaHastaUTC!=null)){
            $fechaDesde=new DateTime($fechaDesdeUTC);
            $fechaHasta=new DateTime($fechaHastaUTC);
            $consulta->whereDate('created_at', '>=', $fechaDesde->format("Y-m-d"))
            ->whereDate('created_at', '<=', $fechaHasta->format("Y-m-d"));
        }

        if($status!="" && $status!=null){
            $consulta->where("status","=",$status);
        }

        if($request!="" && $request!=null){
            $consulta->where("request","=",$request);
        }

        $respuesta=$consulta->paginate($prePage);

        $items=$respuesta->items();

        $tenants = collect($items)->map(function ($model) {
            $id          = Uuid::make($model->id);
            $name        = TenantName::make($model->name);
            $slug        = Slug::make($model->slug);
            $status      = TenantStatus::make($model->status);
            $timezone    = Timezone::make($model->timezone);
            $currency    = Currency::make($model->currency);
            $request     = TenantRequest::make($model->request);
            $created_at  = CreatedAt::fromString($model->created_at);
            $updated_at  = UpdatedAt::fromString($model->updated_at);
            $deleted_at  = SoftDeleteAt::fromString($model->deleted_at);

            $tenant= Tenant::reconstitute(
                $id,
                $name,
                $slug,
                $status,
                $timezone,
                $currency,
                $request,
                $created_at,
                $updated_at,
                $deleted_at,
            );

            return $tenant;
        });

        // 3. Usamos nuestra Collection del dominio
        $domainCollection = new Collection($tenants->all());

        // 4. Devolvemos nuestro objeto de dominio
        return new Pagination(
            $domainCollection,
            $respuesta->total(),
            $respuesta->perPage(),
            $respuesta->currentPage(),
            $respuesta->lastPage()
        );

    }

    public function consultTenantById(Uuid $uuid): ?Tenant
    {
        $consulta= ModelsTenant::where("id","=",$uuid->value())->first();

        $id          = Uuid::make($consulta->id);
        $name        = TenantName::make($consulta->name);
        $slug        = Slug::make($consulta->slug);
        $status      = TenantStatus::make($consulta->status);
        $timezone    = Timezone::make($consulta->timezone);
        $currency    = Currency::make($consulta->currency);
        $request     = TenantRequest::make($consulta->request);
        $created_at  = CreatedAt::fromString($consulta->created_at);
        $updated_at  = UpdatedAt::fromString($consulta->updated_at);
        $deleted_at  = SoftDeleteAt::fromString($consulta->deleted_at);

        $tenant= Tenant::reconstitute(
            $id,
            $name,
            $slug,
            $status,
            $timezone,
            $currency,
            $request,
            $created_at,
            $updated_at,
            $deleted_at,
        );


        $consultaOwner=$consulta->users()->wherePivot("role","owner")->get();

        $owners= collect($consultaOwner)->map(function ($modelOwner){
            $id = Uuid::make($modelOwner->id);
            $name = UserName::make($modelOwner->name);
            $email = UserEmail::make($modelOwner->email);
            $phone = ($modelOwner->phone != null && $modelOwner->phone != "")
            ? PhoneNumber::make($modelOwner->phone)
            : null;
            $type = UserType::make($modelOwner->type);
            $avatar = AvatarUrl::make($modelOwner->avatar);
            $state = UserStatus::make($modelOwner->is_active);

            $create_at = CreatedAt::fromString($modelOwner->created_at);
            $update_at = UpdatedAt::fromString($modelOwner->updated_at);

            return TenantOwner::reconstitute(
                $id,
                $name,
                $email,
                $type,
                $phone,
                $avatar,
                $state,
                $create_at,
                $update_at
            );
        });

        $ownersCollection= new Collection($owners->all());
        $tenant->setOwners($ownersCollection);

        return $tenant;

    }

    public function suspended(Tenant $tenant): Tenant
    {

        $tenant->suspended();

        ModelsTenant::where("id","=",$tenant->getId()->value())
        ->update(["status" => $tenant->getStatus()->value()]);


        return $tenant;




    }

    public function inactive(Tenant $tenant): Tenant
    {

        $tenant->inactive();

        ModelsTenant::where("id","=",$tenant->getId()->value())
        ->update(["status" => $tenant->getStatus()->value()]);

        $tenantDB=ModelsTenant::where("id","=",$tenant->getId()->value())->first();
        tenancy()->initialize($tenantDB);
        $databaseName = $tenantDB->tenancy_db_name;
        tenancy()->end();
        DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$databaseName}`");


        return $tenant;
    }

    public function active(Tenant $tenant): Tenant
    {
        if($tenant->getStatus()->isInactive() && $tenant->getRequest()->isApproved()){
            $tenant->active();
            // $tenantDB=ModelsTenant::where("id","=",$tenant->getId()->value())->first();
            // TODO: hacer que si pasa de inacvtivo a activo se cree la base de datos y se migren las tablas
            // 1. Crear la base de datos (Job)
            // dispatch(new CreateDatabase($tenantDB));
            // 2. Ejecutar migraciones (Job)
            // dispatch(new MigrateDatabase($tenantDB));
            // // 3. Sembrar datos (Opcional)
            // dispatch(new SeedDatabase($tenantDB));

            ModelsTenant::where("id","=",$tenant->getId()->value())
            ->update(["status" => $tenant->getStatus()->value()]);
            return $tenant;

        }
        else{
            $tenant->active();

            ModelsTenant::where("id","=",$tenant->getId()->value())
            ->update(["status" => $tenant->getStatus()->value()]);
            return $tenant;
        }

    }

    public function save(Tenant $tenant): Tenant
    {
        $model= new ModelsTenant();
        $model->id = $tenant->getId()->value();
        $model->name = $tenant->getName()->value();
        $model->slug = $tenant->getSlug()->value();
        $model->status = $tenant->getStatus()->value();
        $model->timezone = $tenant->getTimezone()->value();
        $model->currency = $tenant->getCurrency()->code();
        $model->request = $tenant->getRequest()->value();

        $model->save();

        return $tenant;
    }


}


?>
