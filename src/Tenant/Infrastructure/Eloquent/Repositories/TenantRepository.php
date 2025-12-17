<?php


namespace Src\Tenant\Infrastructure\Eloquent\Repositories;

use DateTime;
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
use Src\Tenant\Domain\ValuesObjects\Slug;
use Src\Tenant\Domain\ValuesObjects\TenantName;
use Src\Tenant\Domain\ValuesObjects\TenantRequest;
use Src\Tenant\Domain\ValuesObjects\TenantStatus;
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

            return Tenant::reconstitute(
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


}


?>
