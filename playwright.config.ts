import { defineConfig, devices } from '@playwright/test';

/*
|--------------------------------------------------------------------------
| Dónde corre la suite
|--------------------------------------------------------------------------
|
| Sin variables, Playwright arranca `php artisan serve` por su cuenta. Antes no existía
| el bloque `webServer`: la suite daba por hecho que habías levantado el servidor a mano
| y, si no, fallaba entera por timeout. Ese fue el motivo real de que los bugs de la
| sesión 2026-08-21 no los detectara nadie — la suite no se ejecutaba.
|
| `PLAYWRIGHT_BASE_URL` apunta a otro servidor; ver la regla de `servidorPropio` abajo.
|
| `PHP_CLI_SERVER_WORKERS` es necesario porque el login de tienda hace una llamada
| servidor-a-servidor a sí mismo (las rutas `interna` de `api-tenant`): con un solo worker el
| servidor embebido de PHP se bloquea esperándose a sí mismo. En Windows el servidor
| embebido ignora esta variable, así que ahí hay que usar `PLAYWRIGHT_BASE_URL` con
| Laragon para los specs que tocan ese flujo.
*/
const baseURL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8000';

/*
| Playwright levanta el servidor cuando la URL apunta al puerto de `artisan serve`.
| Una URL sin puerto (o con el 80) significa que ya hay un vhost real sirviendo —
| Laragon en local— y entonces no se arranca nada.
|
| Con una sola regla quedan cubiertos los tres escenarios: sin variables (127.0.0.1:8000,
| servidor propio), Laragon (`http://owomarket.local`, sin servidor propio) y CI
| (`http://owomarket.local:8000`, servidor propio con los dominios en /etc/hosts).
*/
const servidorPropio = new URL(baseURL).port === '8000';

export default defineConfig({
    testDir: './tests/Frontend/E2E',
    timeout: 30 * 1000,
    expect: {
        timeout: 5000,
    },
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    reporter: 'list',
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    ...(servidorPropio && {
        webServer: {
            command: 'php artisan serve --host=127.0.0.1 --port=8000',
            url: 'http://127.0.0.1:8000',
            reuseExistingServer: !process.env.CI,
            timeout: 60 * 1000,
            env: {
                PHP_CLI_SERVER_WORKERS: '4',
            },
        },
    }),
    projects: [
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                ...(servidorPropio && {
                    launchOptions: {
                        // Los subdominios de tienda no están en el fichero hosts cuando el
                        // servidor lo levanta Playwright, así que se resuelven aquí.
                        args: ['--host-resolver-rules=MAP *.owomarket.local 127.0.0.1, MAP owomarket.local 127.0.0.1'],
                    },
                }),
            },
        },
    ],
});
