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

    /*
    | Los dos asertos van en UN solo test a propósito, y no en dos.
    |
    | Desde N18 el login está limitado a 5 intentos por minuto contra la misma cuenta.
    | Con un test por aserto, dos ejecuciones seguidas de la suite gastaban 4 intentos y
    | la tercera daba 429 — comprobado: la suite se puso intermitente. Con un único login
    | por pasada caben cinco ejecuciones por minuto, que es de sobra para iterar.
    */
    test('un login correcto responde 200 y lleva al backoffice', async ({ page }) => {
        await page.goto(`${tenantBaseURL}/auth/login`);

        const respuestaLogin = page.waitForResponse((r) => r.url().includes('/auth/login') && r.request().method() === 'POST');

        await page.getByPlaceholder('name@owomarket.com').fill(tenantOwner.email);
        await page.getByPlaceholder('password').fill(tenantOwner.password);
        await page.getByRole('button', { name: /Submit/i }).click();

        const respuesta = await respuestaLogin;

        // El aserto que motiva este spec: 419 era exactamente el síntoma de la regresión.
        expect(respuesta.status(), 'El login devolvió 419: la excepción de CSRF para api-tenant/*/interna/* dejó de aplicarse').not.toBe(419);
        expect(respuesta.status()).toBe(200);

        // Que el rol sea `owner` se comprueba por el destino: sólo esa rama redirige aquí.
        await expect(page).toHaveURL(/\/tenant\/backoffice\/[0-9a-f-]+\/dashboard/i);
    });

    test('unas credenciales inválidas no crean sesión', async ({ page }) => {
        await page.goto(`${tenantBaseURL}/auth/login`);

        const respuestaLogin = page.waitForResponse((r) => r.url().includes('/auth/login') && r.request().method() === 'POST');

        // Correo único por ejecución: el límite de N18 cuenta por (cuenta + IP), así que
        // reutilizar siempre el mismo lo agotaría al repetir la suite.
        const correoInexistente = `no.existe.${Date.now()}@chivostore.com`;

        await page.getByPlaceholder('name@owomarket.com').fill(correoInexistente);
        // Cumple las reglas de formato del formulario a propósito: si no, la validación de
        // cliente aborta antes del POST y el test no probaría nada del servidor.
        await page.getByPlaceholder('password').fill('EndAdmin_87654321');
        await page.getByRole('button', { name: /Submit/i }).click();

        // 401 y no 419: distingue «te rechacé las credenciales» de «me rompí».
        expect((await respuestaLogin).status()).toBe(401);
        await expect(page).toHaveURL(/\/auth\/login/);
    });
});
