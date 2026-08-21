# PLAN — Fase 0.3-D: Proteger la API de clientes centrales con una sesión real

> **Origen:** hallazgo A3 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md`, más el hallazgo F4 (guard `central_customer` inexistente) y un bug estructural nuevo: el login central nunca creaba sesión
> **Severidad:** 🔴 Crítico
> **Tamaño:** 1 guard + 1 provider nuevos, 1 middleware reutilizado (`web`), 2 controladores nuevos, 17 controladores modificados, 1 archivo de rutas, 2 archivos de frontend
> **Estado:** ✅ Implementado — pendiente de validación con `php artisan test`
> **Depende de:** Fase 0.3-A (patrón de envolver rutas de `routes/api.php` con `web` para tener sesión)

---

## 1. El problema original (hallazgo A3) — y por qué es más profundo que rutas sin middleware

`src/CentralCustomer/Infrastructure/Http/Routes/apiCentral.php` no tenía middleware alguno. Cada uno de los ~17 endpoints del portal del cliente (perfil, direcciones, pedidos, tracking, facturas, devoluciones, wishlist, reseñas, y la emisión de tokens SSO) tomaba la identidad de donde el cliente la mandara:

```php
$customerId = (string) $request->input('customer_id', $request->header('X-Customer-Id', ''));
```

o directamente de la URL (`/profile/{id}`).

**El hallazgo más grave del bloque:** `POST /api/central/customer/sso/generate-token {"customer_id":"<uuid>"}` emitía un token SSO válido para **cualquier** `customer_id`, sin verificar contraseña ni sesión. Con ese token, cualquiera obtenía una sesión de comprador en cualquier tienda — suplantación total sin credenciales.

### 1.1 Por qué no bastaba con añadir `auth`

Al investigar, encontré que **no existía ningún mecanismo de sesión de cliente en el dominio central**:

- `LoginCentralCustomerPOSTController` validaba email/contraseña correctamente, pero nunca autenticaba nada del lado del servidor. Devolvía un `token` de 64 caracteres aleatorios (`AuthenticateCentralCustomerUseCase.php:33`) que **no se persistía ni se verificaba en ningún punto del código** — código muerto.
- El frontend (`CustomerAuthContext.tsx`) compensaba guardando `customer.id` en `localStorage` y mandándolo como parámetro en cada llamada — el mismo patrón que el hallazgo B4 documenta para el resto del portal.
- La única sesión de cliente que sí existe (`session('central_customer_id')`) la crea `ConsumeSsoTokenPOSTController`, pero eso ocurre en el **dominio del tenant** (`/api-tenant/customer/sso/consume`), no en el central.
- `auth('central_customer')` aparecía en más de diez controladores (soporte incluido, ver Fase 0.3-C) llamando a un guard que **no está registrado** en `config/auth.php` (hallazgo F4) — cada vez que se ejecutaba sin `customer_id` explícito en el request, lanzaba `InvalidArgumentException` (500).

Es decir: proteger las rutas con un guard requería primero construir el guard.

---

## 2. Solución

### 2.1 Guard y provider nuevos (`config/auth.php`)

```php
'guards' => [
    // ...
    'central_customer' => [
        'driver' => 'session',
        'provider' => 'central_customers',
    ],
],

'providers' => [
    // ...
    'central_customers' => [
        'driver' => 'eloquent',
        'model' => CentralCustomer::class,
    ],
],
```

`Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer` ya extendía `Illuminate\Foundation\Auth\User` (Authenticatable) — estaba listo para ser un provider de Auth, simplemente nunca se conectó.

### 2.2 El login ahora crea sesión de verdad

`LoginCentralCustomerPOSTController`, tras validar credenciales:

```php
Auth::guard('central_customer')->login($result['customer']);
$request->session()->regenerate();
```

`$request->session()->regenerate()` evita fijación de sesión (session fixation) en el login.

### 2.3 Logout central nuevo

No existía. `CustomerLogoutCentralPOSTController` (nuevo) hace `Auth::guard('central_customer')->logout()` + `session()->invalidate()` + `regenerateToken()`. Antes, en el dominio central, "cerrar sesión" sólo borraba `localStorage` — no había nada del lado del servidor que cerrar.

### 2.4 Rutas: `web` + `auth:central_customer`

`src/CentralCustomer/Infrastructure/Http/Routes/apiCentral.php` se monta desde `routes/api.php`, bajo el grupo `api` de Laravel — sin sesión ni CSRF por defecto (mismo motivo que la Fase 0.3-A con Monetization). Estructura final:

```php
Route::middleware('web')->group(function () {
    // Público: register, login, forgot-password, reset-password
    Route::middleware('auth:central_customer')->group(function () {
        // logout, sso/generate-token, profile, orders, invoices,
        // returns, reviews, wishlist
    });
    // Público: coupons (catálogo, no depende del comprador)
});
```

### 2.5 Trait `ResolvesAuthenticatedCustomer`

`Src\CentralCustomer\Infrastructure\Http\Support\ResolvesAuthenticatedCustomer`, con dos métodos:

- `currentCustomerId()`: `auth('central_customer')->id()` — nunca el request.
- `denyIfNotOwnProfile(string $profileId)`: para las rutas con `{id}` en la URL (perfil, direcciones), devuelve 403 si `{id}` no coincide con la sesión. Se mantuvo el parámetro de URL (en vez de quitarlo) para no romper las llamadas actuales del frontend — simplemente ya no se puede falsear.

### 2.6 Los 17 controladores

| Patrón anterior | Controladores | Cambio |
| :--- | :--- | :--- |
| `{id}` de la URL sin verificar | `GetCustomerProfileGETController`, `UpdateCustomerProfilePUTController`, `AddCustomerAddressPOSTController`, `UpdateCustomerAddressPUTController`, `DeleteCustomerAddressDELETEController`, `SetDefaultCustomerAddressPATCHController` | `denyIfNotOwnProfile($id)` al inicio |
| `customer_id`/`X-Customer-Id` de query o header | `ListCustomerOrdersGETController`, `GetCustomerOrderDetailGETController`, `GetCustomerOrderTrackingGETController`, `ListCustomerInvoicesGETController`, `DownloadCustomerInvoicePdfGETController`, `ListCustomerReturnsGETController`, `ListCustomerPendingReviewsGETController`, `ListCustomerWishlistGETController` | `currentCustomerId()` reemplaza la lectura del request; se retira el fallback por `email` (quedaba inalcanzable con sesión obligatoria) |
| `customer_id` requerido en el body | `CreateCustomerReturnPOSTController`, `SubmitCustomerReviewPOSTController`, `ToggleCustomerWishlistPOSTController` | se quita de `validate()`, se inyecta `currentCustomerId()` |
| `customer_id` del body sin ninguna verificación | `GenerateSsoTokenPOSTController` | ídem — es el cierre del hallazgo más grave |

Ninguno de los **casos de uso** (`Application/UseCases/*`) se tocó: ya recibían `$customerId` como parámetro separado del resto de los datos, así que el fix quedó contenido en la capa HTTP.

### 2.7 Frontend: logout central

`CustomerAuthServices.ts` gana `logoutCentral()` (`POST /api/central/customer/logout`). `CustomerAuthContext.tsx::logout()` lo llama cuando `isCentralDomain()` es verdadero (antes esa rama no hacía ninguna petición al servidor).

**No hizo falta ningún otro cambio de frontend.** Las llamadas actuales (`baseURL: '/api/central/customer/'`, mismo origen) ya mandan la cookie de sesión automáticamente en cuanto el login la establece — el `customer_id`/`X-Customer-Id` que el frontend sigue mandando en query/body/URL simplemente se ignora del lado del servidor, no rompe nada. El uso de `localStorage` para cachear el perfil (hallazgo B4) sigue ahí y queda fuera del alcance de esta fase — es un problema de UX/caché, no de autorización, ahora que el backend ya no confía en él.

---

## 3. Qué cierra esta fase

| Hallazgo | Estado |
| :--- | :--- |
| **A3** — API de clientes centrales anónima | ✅ Cerrado |
| **A3** — `sso/generate-token` sin verificar contraseña (suplantación total) | ✅ Cerrado |
| **F4** — guard `central_customer` inexistente (dentro de `CentralCustomer`) | ✅ Cerrado |
| Login central que nunca creaba sesión (nuevo, no documentado en la auditoría) | ✅ Cerrado |
| Ausencia de logout central (nuevo) | ✅ Cerrado |

**F4 sigue abierto fuera de este módulo**: no reviso aquí otros guards inexistentes que pudieran aparecer en el resto del código (ninguno detectado durante esta fase).

---

## 4. Tareas

- [x] Registrar guard `central_customer` y provider `central_customers` en `config/auth.php`
- [x] `LoginCentralCustomerPOSTController`: `Auth::guard('central_customer')->login()` + regenerar sesión
- [x] Crear `CustomerLogoutCentralPOSTController` + ruta `POST /logout`
- [x] `GenerateSsoTokenPOSTController`: identidad desde el guard, no desde el body
- [x] Crear trait `ResolvesAuthenticatedCustomer`
- [x] Aplicar el trait en los 17 controladores del portal
- [x] Envolver `apiCentral.php` con `['web']` y el grupo protegido con `['auth:central_customer']`
- [x] Frontend: `logoutCentral()` en `CustomerAuthServices.ts` + `CustomerAuthContext.tsx`
- [x] Actualizar `CentralCustomerAuthTest.php`, `CustomerOrdersAndInvoicesApiTest.php`, `CustomerReturnsAndWishlistApiTest.php`, `CustomerPasswordResetAndProfileTest.php` (sesión vía `actingAs($customer, 'central_customer')`, casos negativos nuevos)
- [ ] `php artisan route:clear && php artisan config:clear`
- [x] `php artisan test` (suite completa — este módulo tiene tests unitarios de casos de uso que no deberían verse afectados, pero conviene confirmarlo)
- [x] `npm run types`
- [x] `vendor/bin/pint src/CentralCustomer/`
- [ ] Verificar manualmente el flujo de login/checkout en el navegador antes de desplegar (ver sección 6) — ⚠️ pendiente: no se verificó en navegador
- [x] Commit: `fix(central-customer): exigir sesión real de comprador en toda la API del portal`
- [x] `git push origin <rama_actual>`
- [x] Mover este documento a `planes/implementados/`

---

## 5. Verificación manual

**Debe seguir funcionando:**
1. Registro → login → ver perfil, pedidos, facturas, devoluciones, wishlist, reseñas pendientes en `/account/*`.
2. Descargar el PDF de una factura propia.
3. Agregar/editar/eliminar una dirección y marcarla como predeterminada.
4. Cerrar sesión desde el dominio central y confirmar que las llamadas posteriores a endpoints protegidos devuelven 401.
5. Iniciar sesión en el dominio central y, desde ahí, comprar en una tienda (flujo de SSO hacia el tenant) — `generateSsoToken` ahora exige la sesión que el login acaba de crear, que ya existe en ese punto del flujo.

**Debe dejar de funcionar:**
6. Sin sesión, cualquier endpoint del grupo protegido → **401** (antes: 200 con los datos de quien fuera el `customer_id`/`{id}` pasado).
7. Con sesión de cliente A, `GET /api/central/customer/profile/{id de B}` → **403**.
8. Con sesión de cliente A, `POST /api/central/customer/sso/generate-token {"customer_id":"<B>"}` → el `customer_id` del body se ignora; el token se emite para A, no para B.
9. Sin sesión, `POST /api/central/customer/sso/generate-token` → **401** (antes: 200 para cualquier `customer_id`).

---

## 6. Riesgo

**Medio-alto — es la fase con más superficie de cambio de todo el bloque A (17 controladores + login + frontend).** Los puntos que vale la pena vigilar en producción:

1. **Cookie de sesión entre subdominios (hallazgo F3).** `session.domain` es un comodín (`.owomarket.local`), así que la sesión que crea el login central debería ser legible por igual en el dominio central. No cambia nada de F3 en sí, pero esta fase es la primera que depende de que esa cookie funcione correctamente para clientes (antes sólo lo usaban dueños de tienda y soporte).
2. **Clientes ya "logueados" en el frontend vía `localStorage` pero sin sesión de servidor** (cualquiera que haya iniciado sesión antes de este despliegue): la UI seguirá mostrando su nombre hasta que refresquen o intenten una acción protegida, momento en el que verán 401 y tendrán que iniciar sesión de nuevo. No hay forma de evitarlo sin migrar sesiones retroactivamente — es aceptable para un cambio de seguridad de esta naturaleza.
3. **CSRF.** Al añadir `web` al grupo, `VerifyCsrfToken` empieza a aplicar sobre estas rutas. `CustomerAuthServices.ts` ya manda `X-CSRF-TOKEN` en sus dos instancias de axios, así que no debería requerir cambios — pero conviene probarlo en el navegador, no sólo en los tests (el cliente de pruebas de Laravel no siempre reproduce el comportamiento real de CSRF).

---

## 7. Trabajo de seguimiento identificado

1. **Hallazgo B4 sigue abierto**: el frontend continúa cacheando el perfil en `localStorage` (`owo_central_customer`, `owo_customer_addresses`). Ya no es una vía de suplantación —el backend no confía en esos datos—, pero sigue siendo una fuga de información si el navegador es compartido o hay XSS. Migrar a que el frontend confíe en la sesión (vía un endpoint `GET /api/central/customer/me` que use el guard) es trabajo de Fase 3 (Frontend).
2. **`sso/generate-token` no verifica el dominio destino contra una lista de tenants activos** — acepta cualquier `target_domain` string. No es explotable para suplantación (ya exige sesión propia), pero podría usarse para generar tokens hacia dominios inexistentes. Menor, no bloqueante.
3. **`/invoices/{id}/pdf` sin sesión redirige a `/auth/login` en vez de devolver 401 limpio** cuando el navegador pide el PDF por navegación directa (sin `Accept: application/json`) — es el comportamiento por defecto del middleware `auth` de Laravel al no encontrar sesión. No es un problema de seguridad (sigue bloqueado), pero la UX de "se venció tu sesión, así no vas a descargar la factura" podría ser mejor. Bajo, no bloqueante.

4. Los use cases de listado (`ListCustomerOrdersUseCase`, `ListCustomerInvoicesUseCase`, `ListCustomerReturnRequestsUseCase`, `ListCustomerPurchasedProductsForReviewUseCase`) conservan el parámetro `$customerEmail` para permitir un futuro flujo de "invitado" real (verificación por número de pedido + email), pero los controladores ya no lo alimentan. Si se retoma esa idea, debe ir en un endpoint aparte, explícitamente limitado y con rate limiting — nunca reabriendo estos mismos endpoints.
