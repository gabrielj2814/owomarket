import { isCentralDomain as guessCentralDomainFromHostname } from '@/Services/CustomerAuthServices';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

/**
 * Dice si estamos en el dominio central o en el de una tienda (hallazgo G7).
 *
 * La versión anterior lo deducía contando las etiquetas del hostname:
 *
 *     const parts = hostname.split('.');
 *     if (parts.length <= 2) return true;   // "mitienda.com" → "central"
 *     return false;                          // "www.mitienda.com" → "tenant"
 *
 * Con eso, una tienda con dominio propio tomaba la rama central al iniciar sesión: no
 * generaba ni consumía el token SSO, así que no se creaba sesión de cliente en la tienda.
 * El usuario veía «Conectado con OwO Pass» en el checkout, pero el pedido se enviaba como
 * invitado. Y con `www.` delante el comportamiento se invertía para el mismo sitio.
 *
 * Ahora lo decide el servidor, que es quien inicializa la tenancy por dominio y por tanto
 * el único que lo sabe de verdad. La heurística queda sólo como respaldo para el caso en
 * que la prop no llegue (una página servida fuera de Inertia).
 */
export function useIsCentralDomain(): boolean {
    const { props } = usePage<SharedData>();

    if (typeof props.is_central === 'boolean') {
        return props.is_central;
    }

    return guessCentralDomainFromHostname();
}
