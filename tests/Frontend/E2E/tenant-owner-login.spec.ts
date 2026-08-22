import { expect, tenantBaseURL, tenantOwner, test } from './fixtures';

/*
|--------------------------------------------------------------------------
| Regresión: el login del dominio de tienda
|--------------------------------------------------------------------------
|
| `LoginWebTenantPOSTController` consulta al usuario a través de una llamada
| servidor-a-servidor a `/api-tenant/user/interna/consulta-usuario-por-email`.
| Cuando la Fase 0.3-E movió `/api-tenant/*` al grupo `web`, esa ruta heredó
| `VerifyCsrfToken` y empezó a responder 419, dejando el login roto durante
| varias fases sin que ninguna suite se enterara:
|
|   - Pest no puede verlo: la llamada interna sale por HTTP real, así que en un
|     feature test o se mockea (y el CSRF nunca se evalúa) o no sale.
|   - Playwright no lo veía porque ningún spec salía del dominio central ni
|     hacía un login.
|
| Este spec cubre justo ese hueco: navegador real, subdominio real, POST real.
*/
test.describe('Login del dueño de tienda (subdominio de tenant)', () => {
    /*
    | Este flujo necesita un servidor con varios procesos. La llamada interna del
    | controlador se hace al MISMO host, así que con un solo worker el servidor se
    | bloquea esperándose a sí mismo y el POST no responde nunca (verificado: cuelga
    | indefinidamente, no es un fallo de CSRF).
    |
    | `php artisan serve` cubre eso con PHP_CLI_SERVER_WORKERS, pero el servidor
    | embebido de PHP ignora esa variable en Windows. Ahí hay que apuntar a Laragon.
    */
    test.skip(
        process.platform === 'win32' && new URL(tenantBaseURL).port === '8000',
        'En Windows este flujo necesita un servidor multiproceso (Laragon). Ejecuta: ' +
            "$env:PLAYWRIGHT_BASE_URL='http://owomarket.local'; npx playwright test",
    );

    test('el POST de login responde 200 y no 419 por CSRF', async ({ page }) => {
        await page.goto(`${tenantBaseURL}/auth/login`);

        const respuestaLogin = page.waitForResponse((r) => r.url().includes('/auth/login') && r.request().method() === 'POST');

        await page.getByPlaceholder('name@owomarket.com').fill(tenantOwner.email);
        await page.getByPlaceholder('password').fill(tenantOwner.password);
        await page.getByRole('button', { name: /Submit/i }).click();

        const respuesta = await respuestaLogin;

        // El assert que importa: 419 era exactamente el síntoma de la regresión.
        expect(respuesta.status(), 'El login devolvió 419: la excepción de CSRF para api-tenant/*/interna/* dejó de aplicarse').not.toBe(419);
        expect(respuesta.status()).toBe(200);

        // No se lee el cuerpo: en cuanto llega el 200 la página hace `window.location.href`
        // al backoffice, y Chrome descarta el cuerpo de una respuesta de la que ya se navegó.
        // Que el rol sea `owner` lo comprueba el test siguiente, por el destino del redirect.
    });

    test('un login correcto lleva al backoffice de la tienda', async ({ page }) => {
        await page.goto(`${tenantBaseURL}/auth/login`);

        await page.getByPlaceholder('name@owomarket.com').fill(tenantOwner.email);
        await page.getByPlaceholder('password').fill(tenantOwner.password);
        await page.getByRole('button', { name: /Submit/i }).click();

        await expect(page).toHaveURL(/\/tenant\/backoffice\/[0-9a-f-]+\/dashboard/i);
    });

    test('unas credenciales inválidas no crean sesión', async ({ page }) => {
        await page.goto(`${tenantBaseURL}/auth/login`);

        const respuestaLogin = page.waitForResponse((r) => r.url().includes('/auth/login') && r.request().method() === 'POST');

        await page.getByPlaceholder('name@owomarket.com').fill(tenantOwner.email);
        // Cumple las reglas de formato del formulario a propósito: si no, la validación de
        // cliente aborta antes del POST y el test no probaría nada del servidor.
        await page.getByPlaceholder('password').fill('EndAdmin_87654321');
        await page.getByRole('button', { name: /Submit/i }).click();

        // 401 y no 419: distingue «te rechacé las credenciales» de «me rompí».
        expect((await respuestaLogin).status()).toBe(401);
        await expect(page).toHaveURL(/\/auth\/login/);
    });
});
