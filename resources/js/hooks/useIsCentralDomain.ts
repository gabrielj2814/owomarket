import { isCentralDomain as guessCentralDomainFromHostname } from '@/Services/CustomerAuthServices';

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
 * invitado. Ahora lo decide el servidor, que es quien inicializa la tenancy por dominio.
 *
 * **Por qué se lee del DOM y no con `usePage()`:** `CustomerAuthProvider` y
 * `CentralCartProvider` envuelven a `<App>` en `app.tsx`, es decir, viven **fuera** del
 * componente de Inertia. Llamar ahí a `usePage()` revienta con «usePage must be used
 * within the Inertia component». El atributo `data-page` del elemento raíz lleva las
 * mismas props compartidas y está disponible sin contexto.
 *
 * Leer sólo la carga inicial es correcto aquí: `is_central` depende del dominio, y una
 * navegación de Inertia nunca cambia de dominio.
 */
function readIsCentralFromInitialPage(): boolean | null {
    if (typeof document === 'undefined') return null;

    // Se busca por `[data-page]` y no por `#app`: el id de la raíz depende de la vista
    // de Inertia y podría cambiar; el atributo no.
    const raw = document.querySelector<HTMLElement>('[data-page]')?.dataset.page;
    if (!raw) return null;

    try {
        const value = JSON.parse(raw)?.props?.is_central;

        return typeof value === 'boolean' ? value : null;
    } catch {
        return null;
    }
}

export function useIsCentralDomain(): boolean {
    const fromServer = readIsCentralFromInitialPage();

    // La heurística del hostname queda sólo como respaldo, para una página servida fuera
    // de Inertia. Ya no decide nada en el flujo real.
    return fromServer ?? guessCentralDomainFromHostname();
}
