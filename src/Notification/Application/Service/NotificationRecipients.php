<?php

declare(strict_types=1);

namespace Src\Notification\Application\Service;

use Illuminate\Support\Collection;
use Src\Shared\Domain\ValueObjects\UserType;
use Src\User\Infrastructure\Eloquent\Models\User;

/**
 * A quién se avisa de cada cosa.
 *
 * Las dos preguntas que el sistema se hace al anunciar algo: **quién de una tienda**, y **quién
 * de la plataforma**. Cualquiera de las dos respondida mal deja un aviso sin dueño.
 *
 * Los dos métodos devuelven el modelo canónico `Src\User\...\User` y no cualquiera de las otras
 * tres clases que hay sobre la tabla `users`: el tipo con el que se guarda un aviso decide quién
 * lo ve después, y mezclarlos haría desaparecer avisos sin dar ningún error.
 */
final class NotificationRecipients
{
    /**
     * Los dueños de una tienda.
     *
     * **Una tienda no tiene buzón: tiene personas.** El pivote `tenant_users` guarda el rol de
     * cada una (`owner`, `admin`, `manager`, `staff`), así que «avisar a la tienda» no significa
     * nada hasta que alguien decide cuáles.
     *
     * Solo los `owner`, y de momento a propósito: son quienes responden por el dinero —una
     * reclamación les cuesta la venta y la reputación—. Avisar a los cuatro roles de todo
     * convertiría el buzón en ruido el primer día, y un buzón que no se lee es el mismo silencio
     * de hoy con más código.
     *
     * Cuando haga falta afinar —que un `manager` reciba los pedidos pero no las reclamaciones—
     * el sitio es aquí, con el tipo de evento por parámetro. No se hace ya porque todavía no hay
     * ninguna tienda pidiéndolo, y adivinarlo ahora es inventarse una regla.
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

    /**
     * Quien atiende la plataforma.
     *
     * Es el mismo criterio que ya usa `MailStaleRateAlerter` para el aviso de tasa obsoleta:
     * superadministradores. Si algún día hay un rol de soporte que también deba verlo, se añade
     * aquí y lo heredan todos los avisos a la vez.
     *
     * @return Collection<int, User>
     */
    public function platformAdmins(): Collection
    {
        return User::query()
            ->where('type', UserType::SUPER_ADMIN)
            ->where('is_active', true)
            ->get();
    }
}
