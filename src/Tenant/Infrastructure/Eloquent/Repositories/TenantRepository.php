<?php

namespace Src\Tenant\Infrastructure\Eloquent\Repositories;

use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Src\Shared\Collection\Collection;
use Src\Shared\Collection\Pagination;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\ValueObjects\AvatarUrl;
use Src\Shared\Domain\ValueObjects\CreatedAt;
use Src\Shared\Domain\ValueObjects\Currency;
use Src\Shared\Domain\ValueObjects\PhoneNumber;
use Src\Shared\Domain\ValueObjects\SoftDeleteAt;
use Src\Shared\Domain\ValueObjects\Timezone;
use Src\Shared\Domain\ValueObjects\UpdatedAt;
use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\Shared\Domain\ValueObjects\UserName;
use Src\Shared\Domain\ValueObjects\UserStatus;
use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\Entities\Domain as EntitiesDomain;
use Src\Tenant\Domain\Entities\Tenant;
use Src\Tenant\Domain\Entities\TenantOwner;
use Src\Tenant\Domain\ValueObjects\Domain;
use Src\Tenant\Domain\ValueObjects\DomainFallback;
use Src\Tenant\Domain\ValueObjects\DomainPrimary;
use Src\Tenant\Domain\ValueObjects\Slug;
use Src\Tenant\Domain\ValueObjects\TenantName;
use Src\Tenant\Domain\ValueObjects\TenantRequest;
use Src\Tenant\Domain\ValueObjects\TenantStatus;
use Src\Tenant\Domain\ValueObjects\UserType;
use Src\Tenant\Infrastructure\Eloquent\Models\Domain as ModelsDomain;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant as ModelsTenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

class TenantRepository implements TenantRepositoryInterface
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected PasswordValidator $validator,
        protected PasswordHasher $hasher,
        protected UuidGenerator $uuidGenerator
    ) {}

    /**
     * Método filter.
     */
    public function filter(
        ?string $search,
        ?string $fechaDesdeUTC,
        ?string $fechaHastaUTC,
        ?string $status,
        ?string $request,
        int $prePage = 50
    ): Pagination {
        $consulta = ModelsTenant::query();

        if ($search != '' && $search != null) {
            $consulta->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        if (($fechaDesdeUTC != '' && $fechaDesdeUTC != null) && ($fechaHastaUTC != '' && $fechaHastaUTC != null)) {
            $fechaDesde = new DateTime($fechaDesdeUTC);
            $fechaHasta = new DateTime($fechaHastaUTC);
            $consulta->whereDate('created_at', '>=', $fechaDesde->format('Y-m-d'))
                ->whereDate('created_at', '<=', $fechaHasta->format('Y-m-d'));
        }

        if ($status != '' && $status != null) {
            $consulta->where('status', '=', $status);
        }

        if ($request != '' && $request != null) {
            $consulta->where('request', '=', $request);
        }

        $respuesta = $consulta->paginate($prePage);

        $items = $respuesta->items();

        $tenants = collect($items)->map(function ($model) {
            $id = Uuid::make($model->id);
            $name = TenantName::make($model->name);
            $slug = Slug::make($model->slug, config('tenancy.central_domains.0'));
            $status = TenantStatus::make($model->status);
            $timezone = Timezone::make($model->timezone);
            $currency = Currency::make($model->currency);
            $request = TenantRequest::make($model->request);
            $created_at = CreatedAt::fromString($model->created_at);
            $updated_at = UpdatedAt::fromString($model->updated_at);
            $deleted_at = SoftDeleteAt::fromString($model->deleted_at);

            $tenant = Tenant::reconstitute(
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

    /**
     * Método consultTenantById.
     */
    public function consultTenantById(Uuid $uuid): ?Tenant
    {
        $consulta = ModelsTenant::where('id', '=', $uuid->value())->first();

        $id = Uuid::make($consulta->id);
        $name = TenantName::make($consulta->name);
        $slug = Slug::make($consulta->slug, config('app.central_domain'));
        $status = TenantStatus::make($consulta->status);
        $timezone = Timezone::make($consulta->timezone);
        $currency = Currency::make($consulta->currency);
        $request = TenantRequest::make($consulta->request);
        $created_at = CreatedAt::fromString($consulta->created_at);
        $updated_at = UpdatedAt::fromString($consulta->updated_at);
        $deleted_at = SoftDeleteAt::fromString($consulta->deleted_at);

        $tenant = Tenant::reconstitute(
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

        $consultaOwner = $consulta->users()->wherePivot('role', 'owner')->get();

        $owners = collect($consultaOwner)->map(function ($modelOwner) {
            $id = Uuid::make($modelOwner->id);
            $name = UserName::make($modelOwner->name);
            $email = UserEmail::make($modelOwner->email);
            $phone = ($modelOwner->phone != null && $modelOwner->phone != '')
            ? PhoneNumber::make($modelOwner->phone)
            : null;
            $type = UserType::make($modelOwner->type);
            $avatar = AvatarUrl::make($modelOwner->avatar);
            $state = UserStatus::make($modelOwner->is_active);

            $create_at = CreatedAt::fromString($modelOwner->created_at);
            $update_at = UpdatedAt::fromString($modelOwner->updated_at);
            $softDelete = ($modelOwner->deleted_at != null) ? SoftDeleteAt::fromString($modelOwner->deleted_at) : null;

            $password = null; // No se recupera la contraseña por seguridad
            $emailVerifiedAt = null;
            $pin = null;

            return TenantOwner::reconstitute(
                $id,
                $name,
                $email,
                $password,
                $emailVerifiedAt,
                $pin,
                $type,
                $phone,
                $avatar,
                $state,
                $create_at,
                $update_at,
                $softDelete
            );
        });

        $ownersCollection = new Collection($owners->all());
        $tenant->setOwners($ownersCollection);

        return $tenant;

    }

    /**
     * Método suspended.
     */
    public function suspended(Tenant $tenant): Tenant
    {

        $tenant->suspended();

        ModelsTenant::where('id', '=', $tenant->getId()->value())
            ->update(['status' => $tenant->getStatus()->value()]);

        return $tenant;

    }

    /**
     * Método inactive.
     */
    public function inactive(Tenant $tenant): Tenant
    {

        $tenant->inactive();

        ModelsTenant::where('id', '=', $tenant->getId()->value())
            ->update(['status' => $tenant->getStatus()->value()]);

        $tenantDB = ModelsTenant::where('id', '=', $tenant->getId()->value())->first();
        tenancy()->initialize($tenantDB);
        $databaseName = $tenantDB->tenancy_db_name;
        tenancy()->end();
        DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$databaseName}`");

        return $tenant;
    }

    /**
     * Método active.
     */
    public function active(Tenant $tenant): Tenant
    {
        if ($tenant->getStatus()->isInactive() && $tenant->getRequest()->isApproved()) {
            $tenant->active();
            // $tenantDB=ModelsTenant::where("id","=",$tenant->getId()->value())->first();
            // TODO: hacer que si pasa de inacvtivo a activo se cree la base de datos y se migren las tablas
            // 1. Crear la base de datos (Job)
            // dispatch(new CreateDatabase($tenantDB));
            // 2. Ejecutar migraciones (Job)
            // dispatch(new MigrateDatabase($tenantDB));
            // // 3. Sembrar datos (Opcional)
            // dispatch(new SeedDatabase($tenantDB));

            ModelsTenant::where('id', '=', $tenant->getId()->value())
                ->update(['status' => $tenant->getStatus()->value()]);

            return $tenant;

        } else {
            $tenant->active();

            ModelsTenant::where('id', '=', $tenant->getId()->value())
                ->update(['status' => $tenant->getStatus()->value()]);

            return $tenant;
        }

    }

    /**
     * Método save.
     */
    public function save(Tenant $tenant): Tenant
    {
        Log::info($tenant->getId()->value());
        $model = new ModelsTenant;
        $model->id = $tenant->getId()->value();
        $model->name = $tenant->getName()->value();
        $model->slug = $tenant->getSlug()->value();
        $model->status = $tenant->getStatus()->value();
        $model->timezone = $tenant->getTimezone()->value();
        $model->currency = $tenant->getCurrency()->code();
        $model->request = $tenant->getRequest()->value();

        $model->save();

        $this->tenantUp($tenant->getId());

        return $tenant;
    }

    /**
     * Método consultTenantBySlug.
     */
    public function consultTenantBySlug(Slug $slug): ?Tenant
    {
        $consulta = ModelsTenant::where('slug', '=', $slug->value())->first();
        if ($consulta === null) {
            return null;
        }

        $id = Uuid::make($consulta->id);
        $name = TenantName::make($consulta->name);
        $slug = Slug::make($consulta->slug, config('app.central_domain'));
        $status = TenantStatus::make($consulta->status);
        $timezone = Timezone::make($consulta->timezone);
        $currency = Currency::make($consulta->currency);
        $request = TenantRequest::make($consulta->request);
        $created_at = CreatedAt::fromString($consulta->created_at);
        $updated_at = UpdatedAt::fromString($consulta->updated_at);
        $deleted_at = SoftDeleteAt::fromString($consulta->deleted_at);

        $tenant = Tenant::reconstitute(
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
    }

    /**
     * Método deleteTenant.
     */
    public function deleteTenant(Uuid $id): bool
    {
        $record = ModelsTenant::where('id', $id->value())->first();
        if ($record) {
            $record->delete();

            return true;
        }

        return false;
    }

    /**
     * Método deleteForceTenant.
     */
    public function deleteForceTenant(Uuid $id): bool
    {
        $record = ModelsTenant::withTrashed()->where('id', $id->value())->first();
        if ($record) {
            $record->forceDelete();

            return true;
        }

        return false;
    }

    /**
     * Método tenantUp.
     */
    public function tenantUp(Uuid $id)
    {
        $tenant = ModelsTenant::where('id', $id->value())->first();
        try {
            $tenant->domains()->create([
                'id' => Uuid::generate($this->uuidGenerator)->value(),
                'domain' => Domain::fromString($tenant->slug.'.'.config('tenancy.central_domains.0'))->value(),
            ]);

            tenancy()->initialize($tenant);
            $user = new User;
            $user->id = Uuid::generate($this->uuidGenerator)->value();
            $user->name = 'Admin';
            $user->email = 'admin@'.$tenant->slug.'.com';
            $user->password = $this->hasher->hash(config('app.default_passwords_tenant_owner'));
            $user->type = 'owner';
            $user->is_active = true;
            $user->save();

        } finally {
            tenancy()->end();
        }
    }

    /**
     * Método changedRequestStatus.
     */
    public function changedRequestStatus(Tenant $tenant): Tenant
    {
        ModelsTenant::where('id', '=', $tenant->getId()->value())
            ->update(['request' => $tenant->getRequest()->value()]);

        return $tenant;
    }

    /**
     * Método consultTenantsByIdOwnerPaginate.
     */
    public function consultTenantsByIdOwnerPaginate(Uuid $uuid, int $prePage = 50): Pagination
    {

        $consulta = ModelsTenant::whereHas('users', function ($query) use ($uuid) {
            $query->where('tenant_users.user_id', $uuid->value())
                ->where('tenant_users.role', 'owner');
        });

        $respuesta = $consulta->paginate(50);

        $items = $respuesta->items();

        $tenants = collect($items)->map(function ($model) {
            $id = Uuid::make($model->id);
            $name = TenantName::make($model->name);
            $slug = Slug::make($model->slug, config('tenancy.central_domains.0'));
            $status = TenantStatus::make($model->status);
            $timezone = Timezone::make($model->timezone);
            $currency = Currency::make($model->currency);
            $request = TenantRequest::make($model->request);
            $created_at = CreatedAt::fromString($model->created_at);
            $updated_at = UpdatedAt::fromString($model->updated_at);
            $deleted_at = SoftDeleteAt::fromString($model->deleted_at);

            $tenant = Tenant::reconstitute(
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

            $domain = ModelsDomain::where('tenant_id', '=', $tenant->getId()->value())->first();
            if ($domain) {
                $tenant->setDomain(EntitiesDomain::reconstitute(
                    Uuid::make($domain->id),
                    Uuid::make($domain->tenant_id),
                    Domain::fromString($domain->domain),
                    DomainPrimary::make($domain->is_primary),
                    DomainFallback::make($domain->is_fallback),
                    CreatedAt::fromString($domain->created_at),
                    UpdatedAt::fromString($domain->updated_at),
                ));
            }

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
}
