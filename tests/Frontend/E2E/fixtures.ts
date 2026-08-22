import { test as base, expect } from '@playwright/test';

/**
 * `test` extendido que falla ante cualquier excepción no capturada de la página.
 *
 * Motivo (sesión 2026-08-22): el hallazgo G7 —`useIsCentralDomain` llamando a `usePage()`
 * desde un provider que vive FUERA del componente de Inertia— reventaba el montaje de React
 * en todas las páginas. Los specs de entonces sólo comprobaban `response.status() < 400`, y
 * Laravel sigue devolviendo 200 con el shell de HTML aunque el JS de dentro explote.
 *
 * Escuchar `pageerror` cubre toda la clase de bug de una vez, en cualquier página presente o
 * futura, sin tener que acordarse de añadir un assert por página.
 *
 * Sólo se vigila `pageerror` (excepciones no capturadas), no `console.error`: lo segundo es
 * ruidoso —404 de favicon, avisos de librerías— y convertiría la suite en un generador de
 * falsos positivos. Una excepción no capturada, en cambio, nunca es aceptable.
 */
export const test = base.extend<{ failOnPageError: void }>({
    failOnPageError: [
        async ({ page }, use) => {
            const errors: Error[] = [];

            page.on('pageerror', (error) => errors.push(error));

            await use();

            if (errors.length > 0) {
                const detalle = errors.map((e) => `  - ${e.message}`).join('\n');

                throw new Error(`La página lanzó ${errors.length} excepción(es) no capturada(s):\n${detalle}`);
            }
        },
        { auto: true },
    ],
});

export { expect };

/**
 * URL base de una tienda (subdominio de tenant).
 *
 * Ningún spec salía del dominio central, y por eso el 419 del login de tienda —que llevaba
 * roto desde la Fase 0.3-E— no lo vio nadie hasta que se probó a mano en el navegador.
 *
 * Se deriva del `baseURL` sustituyendo el host y conservando el puerto, para que funcione
 * igual con el `php artisan serve` que levanta Playwright (`:8000`, con los subdominios
 * resueltos por `--host-resolver-rules`) y con Laragon (`PLAYWRIGHT_BASE_URL`).
 *
 * `chivostore` lo siembra `TenantDomainSeeder`, y su dueño `TenantDefaultUsersSeeder`.
 */
export const tenantBaseURL = (() => {
    if (process.env.PLAYWRIGHT_TENANT_BASE_URL) {
        return process.env.PLAYWRIGHT_TENANT_BASE_URL;
    }

    const url = new URL(process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8000');
    url.hostname = 'chivostore.owomarket.local';

    return url.origin;
})();

/**
 * Credenciales del dueño de `chivostore`, tal como las siembra `TenantDomainSeeder`.
 *
 * Ojo: este usuario vive en la **base de datos del tenant**, que es a la que consulta
 * `UserApiTenantClient`. El `chivostore.owner@owomarket.local` de `TenantDefaultUsersSeeder`
 * está en la base central y sirve para el hub central, no para el login de la tienda:
 * usarlo aquí devuelve 401 «El usuario no encontrado».
 */
export const tenantOwner = {
    email: 'admin@chivostore.com',
    password: process.env.PLAYWRIGHT_TENANT_OWNER_PASSWORD || 'EndAdmin_12345678',
};

/**
 * Segunda tienda, usada SÓLO por el spec que prueba el login.
 *
 * El límite de N18 cuenta por (cuenta + IP). Con una sola cuenta, la suite gastaba dos
 * intentos por pasada —uno el setup de sesión, otro el propio spec de login— y a la
 * tercera ejecución dentro del minuto empezaba a dar 429. Comprobado.
 *
 * Repartir en dos cuentas da a cada una su propio cupo: un intento por pasada cada una,
 * cinco ejecuciones por minuto. `tecs` sirve porque este spec sólo comprueba que el login
 * funcione y aterrice en un backoffice; los datos los mira el spec de backoffice, que
 * sigue en `chivostore`.
 */
export const tenantOwnerAlterno = {
    baseURL: tenantBaseURL.replace('chivostore.', 'tecs.'),
    email: 'admin@tecs.com',
    password: process.env.PLAYWRIGHT_TENANT_OWNER_PASSWORD || 'EndAdmin_12345678',
};

/*
|--------------------------------------------------------------------------
| Sesión compartida
|--------------------------------------------------------------------------
|
| `auth.setup.ts` inicia sesión una vez y deja aquí las cookies y el uuid del
| propietario. El límite de tasa de N18 —5 intentos por minuto contra la misma cuenta—
| hacía intermitente la suite cuando cada spec de backoffice iniciaba el suyo.
|
| Las rutas viven en este módulo y no en el propio setup porque Playwright no deja que un
| fichero de test importe a otro.
*/
export const ESTADO_SESION = 'test-results/.auth/tenant-owner.json';

/** El uuid del propietario va en la URL del backoffice; las cookies solas no lo llevan. */
export const FICHERO_UUID = 'test-results/.auth/tenant-owner-uuid.txt';
