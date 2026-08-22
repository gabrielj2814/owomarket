<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Throwable;

/**
 * Crea el vocabulario de permisos dentro de la base de cada tienda (hallazgo N19).
 *
 * La Fase 4.2 dejó las TABLAS de Spatie en cada inquilino, pero nadie las llenaba: sin
 * filas en `permissions`, `tenant_can` no tiene nada que conceder y todo `staff` se
 * queda sin poder escribir. Este comando es el que hace utilizable el control de rol.
 *
 * Es idempotente: se puede correr tantas veces como haga falta, y hay que correrlo tras
 * dar de alta una tienda nueva.
 *
 * El propietario NO necesita ninguna de estas filas: `tenant_can` lo deja pasar siempre,
 * a propósito, para que un fallo de aprovisionamiento no lo deje fuera de su negocio.
 */
final class ProvisionTenantRolesCommand extends Command
{
    protected $signature = 'tenant:provision-roles {--tenant= : Slug o id de una tienda concreta}';

    protected $description = 'Crea los permisos y el rol de gerente dentro de la base de cada tienda';

    /**
     * Un permiso por área de responsabilidad, no por módulo: quien lleva el catálogo toca
     * productos, categorías, marcas y atributos, y concederlos por separado sólo daría
     * trabajo de configuración sin ganar control real.
     *
     * @var array<string, string>
     */
    private const PERMISOS = [
        'manage_catalog' => 'Crear, editar y borrar productos, categorías, marcas y atributos',
        'manage_orders' => 'Modificar pedidos y envíos',
        'manage_billing' => 'Emitir y anular facturas, y registrar pagos',
        'manage_settings' => 'Cambiar impuestos, envíos y ajustes de la tienda',
        'manage_customers' => 'Editar la ficha de los clientes',
        'manage_coupons' => 'Crear y retirar cupones',
        'manage_reviews' => 'Moderar reseñas',
    ];

    /** Rol de conveniencia: todo salvo lo que sólo puede el propietario. */
    private const ROL_GERENTE = 'gerente';

    public function handle(): int
    {
        $tenants = Tenant::query()
            ->when($this->option('tenant'), fn ($q, $t) => $q->where('id', $t)->orWhere('slug', $t))
            ->get();

        if ($tenants->isEmpty()) {
            $this->error('No se encontró ninguna tienda con ese criterio.');

            return self::FAILURE;
        }

        $fallos = 0;

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                // Spatie cachea el mapa de permisos por proceso. Sin vaciarlo, la segunda
                // tienda del bucle vería los permisos de la primera.
                app(PermissionRegistrar::class)->forgetCachedPermissions();

                foreach (self::PERMISOS as $nombre => $_) {
                    Permission::findOrCreate($nombre, 'web');
                }

                Role::findOrCreate(self::ROL_GERENTE, 'web')
                    ->syncPermissions(array_keys(self::PERMISOS));

                $this->line("  {$tenant->name}: ".count(self::PERMISOS).' permisos y el rol «'.self::ROL_GERENTE.'»');
            } catch (Throwable $e) {
                // Lo mas probable es que a esa tienda le falten las migraciones de
                // Spatie (`tenants:migrate`). Cuenta como fallo: devolver SUCCESS con
                // tiendas sin aprovisionar dejaria a su staff sin poder escribir nada y
                // nadie se enteraria hasta que alguien se quejara.
                $fallos++;
                $this->error("  {$tenant->name}: {$e->getMessage()}");
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        $correctas = $tenants->count() - $fallos;
        $this->info("✅ Roles aprovisionados en {$correctas} de {$tenants->count()} tienda(s).");

        if ($fallos > 0) {
            $this->warn("⚠️  {$fallos} tienda(s) sin aprovisionar. Revisa que tengan las migraciones al día: php artisan tenants:migrate");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
