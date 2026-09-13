<?php

declare(strict_types=1);

namespace Src\Notification\Application\Service;

use Illuminate\Support\Collection;
use Src\User\Infrastructure\Eloquent\Models\User;

/**
 * A quién de una tienda se avisa.
 *
 * **Una tienda no tiene buzón: tiene personas.** El pivote `tenant_users` guarda el rol de cada
 * una (`owner`, `admin`, `manager`, `staff`), así que «avisar a la tienda» no significa nada
 * hasta que alguien decide cuáles.
 *
 * ## Solo los dueños, y de momento a propósito
 *
 * Los `owner` son quienes responden por el dinero: una reclamación les cuesta la venta y la
 * reputación. Avisar a los cuatro roles de todo convertiría el buzón en ruido el primer día, y
 * un buzón que no se lee es el mismo silencio de hoy con más código.
 *
 * Cuando haga falta afinar —que un `manager` reciba los pedidos pero no las reclamaciones— el
 * sitio de hacerlo es aquí, con el tipo de evento por parámetro. No se hace ya porque todavía
 * no hay ninguna tienda pidiéndolo, y adivinarlo ahora es inventarse una regla.
 */
final class TenantRecipients
{
    /**
     * Los dueños de una tienda, como destinatarios.
     *
     * Devuelve el modelo canónico `Src\User\...\User` y no cualquiera de las otras tres clases
     * que hay sobre la tabla `users`: el tipo con el que se guarda un aviso decide quién lo ve
     * después, y mezclarlos haría desaparecer avisos sin dar ningún error.
     *
     * @return Collection<int, User>
     */
    public function owners(string $tenantId): Collection
    {
        return User::query()
            ->whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId)
                ->where('tenant_users.role', 'owner'))
            ->get();
    }
}
