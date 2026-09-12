<?php

declare(strict_types=1);

namespace Src\Marketplace\Infrastructure\Http\Support;

/**
 * Quién es el comprador que navega un escaparate.
 *
 * En el dominio central la identidad del comprador la da el guard `central_customer`. En el
 * dominio de una tienda **no hay guard**: la sesión la crea `ConsumeSsoTokenPOSTController` al
 * canjear el token SSO, y deja ahí los dos identificadores.
 *
 * El que interesa es el CENTRAL, porque es con el que los subsistemas 3 y 5 conocen al
 * comprador --`OrderDeliveryConfirmation.customer_id` guarda ese, no el de la tienda--.
 *
 * Existe como trait y no repetido en cada controlador para que la frontera de confianza del
 * escaparate se lea en un solo sitio. El día que cambie dónde vive esa identidad, cambia aquí.
 */
trait ResolvesStorefrontCustomer
{
    /** Cadena vacía si nadie ha iniciado sesión: el que llama decide qué hacer con eso. */
    private function currentStorefrontCustomerId(): string
    {
        return (string) (session('central_customer_id') ?? '');
    }
}
