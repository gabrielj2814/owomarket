import fs from 'node:fs';
import { expect, FICHERO_UUID, tenantBaseURL, test } from './fixtures';

/*
|--------------------------------------------------------------------------
| Hallazgo N29 — que el backoffice pinte datos de verdad
|--------------------------------------------------------------------------
|
| La deuda de tipos no era cosmética. Las páginas leían la respuesta un nivel más
| adentro de lo que existe (`res.data.data` sobre un servicio que ya devuelve el cuerpo),
| y comparaban `res.status` con códigos HTTP cuando ahí viaja «success» | «error». Esas
| condiciones no podían cumplirse nunca, así que la lista se quedaba vacía y toda acción
| respondía con un aviso de error aunque hubiera funcionado.
|
| Ningún test lo veía porque nadie entraba al backoffice con un navegador. Este spec sí.
*/
test.describe('Backoffice de la tienda', () => {
    // La sesion la deja `auth.setup.ts`, que corre antes. Sin cookies —el caso de Windows
    // con el servidor propio, donde el login no puede completarse— no hay backoffice que
    // mirar y estos tests se saltan solos en vez de fallar por algo ajeno.
    test.skip(
        process.platform === 'win32' && new URL(tenantBaseURL).port === '8000',
        'Necesita un servidor multiproceso (Laragon) por la llamada interna del login.',
    );

    /** Lo anota `auth.setup.ts` al aterrizar en el dashboard. */
    const uuidDelPropietario = (): string => fs.readFileSync(FICHERO_UUID, 'utf-8').trim();

    /*
    | Esta es la pagina que estaba rota, no la de productos.
    |
    | `CustomerIndexPage` leia `res.data.data.data` sobre un servicio que ya devuelve el
    | cuerpo. Con el sobre anidado que tenia entonces `/customer/filter`, `res.data.data`
    | existia —era el array— asi que la condicion pasaba, pero un nivel mas adentro no
    | habia nada y la tabla se llenaba con `[]`. Las metricas ni eso.
    |
    | N37 unifico despues los seis sobres en uno, asi que hoy `data` es directamente la
    | lista; el test sigue valiendo porque comprueba lo que se ve en pantalla.
    |
    | La de productos ya era defensiva y funcionaba, por eso no sirve como regresion; se
    | conserva para cubrir el otro sobre de paginacion.
    */
    test('el listado de clientes se llena con los clientes de la tienda', async ({ page }) => {
        const uuid = uuidDelPropietario();

        const respuestaFiltro = page.waitForResponse((r) => r.url().includes('/api-tenant/customer/filter') && r.request().method() === 'POST');

        await page.goto(`${tenantBaseURL}/customer/backoffice/${uuid}/module`);

        const cuerpo = await (await respuestaFiltro).json();
        // Desde N37 `data` es siempre la lista, en todos los listados de la API.
        const clientes = cuerpo.data ?? [];

        expect(clientes.length, 'La tienda de pruebas no tiene clientes; siembra datos').toBeGreaterThan(0);

        await expect(page.getByText(clientes[0].name as string, { exact: false }).first()).toBeVisible();
    });

    test('el listado de productos se llena con los productos de la tienda', async ({ page }) => {
        const uuid = uuidDelPropietario();

        const respuestaFiltro = page.waitForResponse((r) => r.url().includes('/api-tenant/product/filter') && r.request().method() === 'POST');

        await page.goto(`${tenantBaseURL}/product/backoffice/${uuid}/module`);

        const cuerpo = await (await respuestaFiltro).json();
        const productos = cuerpo.data ?? [];

        expect(productos.length, 'La tienda de pruebas no tiene productos; siembra el catalogo').toBeGreaterThan(0);
        await expect(page.getByText(productos[0].name as string, { exact: false }).first()).toBeVisible();
    });
});
