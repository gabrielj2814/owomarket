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

/*
| Los subdominios de tienda no estan en el fichero hosts cuando el servidor lo levanta
| Playwright, asi que se resuelven aqui. Lo comparten los tres proyectos.
*/
const opcionesDeArranque = servidorPropio
    ? {
          launchOptions: {
              args: ['--host-resolver-rules=MAP *.owomarket.local 127.0.0.1, MAP owomarket.local 127.0.0.1'],
          },
      }
    : {};

/** Cookies del propietario, que deja `auth.setup.ts` y reutiliza el resto de la suite. */
const ESTADO_SESION = 'test-results/.auth/tenant-owner.json';

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
    /*
    | El login va en un proyecto propio que corre primero y guarda las cookies. Sin esto
    | cada spec de backoffice hacia su propio login, y el limite de N18 —5 por minuto
    | contra la misma cuenta— ponia la suite intermitente al ejecutarla dos veces
    | seguidas (comprobado). Ahora hay un solo login por pasada, y anadir specs de
    | backoffice ya no cuesta intentos.
    |
    | `tenant-owner-login.spec.ts` queda FUERA de la sesion compartida: prueba el login
    | en si, asi que tiene que empezar sin sesion.
    */
    projects: [
        {
            name: 'setup',
            testMatch: /.*\.setup\.ts/,
            use: { ...devices['Desktop Chrome'], ...opcionesDeArranque },
        },
        {
            name: 'login',
            testMatch: /tenant-owner-login\.spec\.ts/,
            use: { ...devices['Desktop Chrome'], ...opcionesDeArranque },
        },
        {
            name: 'chromium',
            testIgnore: [/.*\.setup\.ts/, /tenant-owner-login\.spec\.ts/],
            dependencies: ['setup'],
            use: {
                ...devices['Desktop Chrome'],
                ...opcionesDeArranque,
                storageState: ESTADO_SESION,
            },
        },
    ],
});
