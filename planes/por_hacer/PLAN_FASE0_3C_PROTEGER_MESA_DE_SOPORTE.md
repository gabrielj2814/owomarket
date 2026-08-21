# PLAN — Fase 0.3-C: Proteger la mesa de soporte central y evitar la suplantación de agentes

> **Origen:** hallazgo A6 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md`, más varios bugs nuevos descubiertos al leer los casos de uso
> **Severidad:** 🔴 Crítico
> **Tamaño:** 1 middleware nuevo, 1 trait nuevo, 6 controladores modificados, 2 casos de uso modificados, 1 archivo de rutas
> **Estado:** ✅ Implementado y validado — `SupportTicketLifecycleTest` y `TenantStoreSupportApiTest` en verde
> **Depende de:** Fase 0.2 (registro de alias de middleware en `bootstrap/app.php`)

---

## 1. El problema original (hallazgo A6)

`src/SupportTicket/Infrastructure/Http/Routes/web.php` monta el grupo `api/support` sin ningún middleware. Este archivo se carga **dos veces** desde `routes/web.php` — sin prefijo (clientes, vía `/account/support`) y bajo `/tenant` (propietarios de tienda, vía `/tenant/owner/backoffice/{uuid}/support`) — así que el agujero afecta a ambos flujos:

```php
Route::prefix('api/support')->group(function () {
    Route::get('/tickets', ListSupportTicketsGETController::class);
    Route::post('/tickets', CreateSupportTicketPOSTController::class);
    Route::get('/tickets/{id}', GetSupportTicketDetailGETController::class);
    Route::post('/tickets/{id}/messages', AddSupportTicketMessagePOSTController::class);
    Route::patch('/tickets/{id}/status', UpdateSupportTicketStatusPATCHController::class);
});
```

Los cinco controladores tomaban la identidad del cuerpo o la query string antes que de la sesión:

```php
$userId = (string) ($request->query('user_id')
    ?: $request->input('user_id')
    ?: auth('central_customer')->id()
    ?: auth()->id());
```

**Escenarios de la auditoría:**
- `GET /api/support/tickets/{uuid}` sin parámetros → devuelve el ticket completo de cualquiera.
- `POST /api/support/tickets/{id}/messages {"message":"Confirme su contraseña","sender_type":"admin","sender_name":"Soporte OwoMarket"}` → inserta un mensaje que la víctima ve como oficial. Phishing dentro del propio producto.

---

## 2. Lo que la auditoría no vio: tres bugs más, y por qué el fix es más profundo

### 2.1 🔴 `auth('central_customer')` es una llamada muerta — el guard nunca existió (hallazgo F4)

`config/auth.php` sólo define los guards `web` y `central`. `central_customer` no está registrado, así que **cada vez que un controlador de soporte llegaba a evaluar `auth('central_customer')->id()` sin que hubiera `user_id` en el request, lanzaba `InvalidArgumentException` y devolvía 500** — nunca fue una ruta de identificación real.

Y la identidad del cliente central **no vive en un guard de Auth de Laravel**. `ConsumeSsoTokenPOSTController.php:33` la escribe como una clave de sesión simple:

```php
session(['central_customer_id' => $result['central_customer']['id']]);
```

`DownloadCustomerInvoicePdfGETController.php:23` ya usa ese patrón correctamente. El resto del proyecto, no.

### 2.2 🔴 `AddMessageToTicketUseCase` nunca verificaba que el ticket perteneciera a quien escribe

Ni con `auth` habría bastado: el caso de uso sólo comprobaba que el ticket existiera, no que `sender_id` coincidiera con `ticket->user_id`. **Cualquier sesión válida** —de cualquier cliente o cualquier propietario de tienda— podía escribir en el ticket de otra persona con sólo saber el UUID. Esto también afecta a la ruta del contexto de tienda (`AddTenantStoreSupportMessagePOSTController`, que sí exige `auth` pero comparte el mismo caso de uso sin pasar el ID del solicitante).

### 2.3 🔴 `UpdateSupportTicketStatusPATCHController` no tenía middleware NI verificación de propiedad

La ruta compartida no llevaba ni `auth`, y `UpdateTicketStatusUseCase` no comprobaba dueño. Cualquiera podía cerrar o reabrir el ticket de cualquier otra persona.

### 2.4 🟠 La vista `/account/support` leía una clave de sesión que nadie escribe

`ViewCustomerSupportGETController.php:21` hacía `session('customer_id') ?: $request->header('X-User-Id') ?: $request->query('user_id') ?: auth()->id()`. `customer_id` (sin el prefijo `central_`) no se asigna en ningún punto del proyecto — es una clave muerta heredada de una versión anterior. En la práctica, la página siempre caía en la cabecera `X-User-Id` o en `?user_id=`, e insertaba los tickets de **cualquier** UUID indicado directamente en las props de Inertia (SSR), sin pasar por la API.

### 2.5 🟠 `sender_type`/`requester_type` eran de confianza del cliente

`CreateSupportTicketPOSTController` aceptaba `requester_type: staff` desde el body sin ninguna verificación detrás, y `AddSupportTicketMessagePOSTController` aceptaba `sender_type: admin|support_agent`. Ninguno de los dos valores tiene sentido viniendo de un endpoint público — el envío como staff real vive en `AdminReplySupportTicketPOSTController`, ya protegido por `['auth','staff:manage_support']` desde la Fase 0.3-A.

---

## 3. Solución

### 3.1 Nuevo middleware: `Src\Shared\Infrastructure\Http\Middleware\EnsureSupportRequesterIsAuthenticated`

Alias registrado en `bootstrap/app.php`: `support_session`.

La mesa de soporte tiene dos tipos de sesión legítima, resueltas por mecanismos distintos (guard `web` de Laravel para propietarios; `session('central_customer_id')` para clientes). Este middleware sólo exige que **una de las dos** exista:

```php
$hasTenantOwnerSession = $request->user() !== null;
$hasCustomerSession = $request->session()->has('central_customer_id');

if (! $hasTenantOwnerSession && ! $hasCustomerSession) {
    return ApiResponse::error('Debes iniciar sesión para acceder a soporte.', 401);
}
```

### 3.2 Nuevo trait: `Src\SupportTicket\Infrastructure\Http\Support\ResolvesSupportRequester`

Única fuente de verdad para "¿quién hace esta petición?" en los controladores públicos de soporte. Nunca lee `user_id`, `sender_type` ni `requester_type` del request:

```php
private function resolveSupportRequester(Request $request): ?array
{
    $user = $request->user();
    if ($user !== null) {
        return ['id' => (string) $user->id, 'type' => 'tenant_owner', 'name' => ...];
    }

    $customerId = $request->session()->get('central_customer_id');
    if ($customerId) {
        return ['id' => (string) $customerId, 'type' => 'customer', 'name' => ...];
    }

    return null;
}
```

### 3.3 Controladores modificados

| Archivo | Cambio |
| :--- | :--- |
| `ListSupportTicketsGETController` | Identidad del trait. Sin sesión → 401 (antes: 400 "user_id obligatorio"). |
| `GetSupportTicketDetailGETController` | Identidad del trait. Ya no admite ver el ticket de otro pasando `user_id` en la query. |
| `CreateSupportTicketPOSTController` | Identidad y `requester_type` del trait — ya no se aceptan del body. Se quitan `user_id`/`requester_type` de `validate()`. |
| `AddSupportTicketMessagePOSTController` | Identidad y `sender_type` del trait — ya no se aceptan del body (adiós `sender_type: admin` de un cliente). Pasa `requesterId` al caso de uso para la verificación de propiedad. |
| `UpdateSupportTicketStatusPATCHController` | Exige sesión (antes: ninguna) y pasa `requesterId` al caso de uso. |
| `ViewCustomerSupportGETController` | Usa el trait en vez de `session('customer_id')` / cabecera / query. Sin sesión → tickets vacíos, no los de un UUID arbitrario. |

`AddTenantStoreSupportMessagePOSTController` (contexto de tienda, ya protegido por `auth`) también pasa ahora `requesterId` al caso de uso compartido, para beneficiarse de la verificación de propiedad de la sección 3.4.

### 3.4 Casos de uso modificados: verificación de propiedad

`AddMessageToTicketUseCase::execute()` y `UpdateTicketStatusUseCase::execute()` ganan un parámetro `?string $requesterId = null`:

- Si es `null` → ruta de staff (`AdminReplySupportTicketPOSTController`, `UpdateAdminSupportTicketStatusPATCHController`, ya protegidas por `staff:manage_support` desde la 0.3-A): puede operar sobre cualquier ticket, como debe ser.
- Si viene informado → se exige `$ticket->user_id === $requesterId`, o lanza `403 No tienes acceso a este ticket de soporte.`

Es exactamente el mismo patrón que `TenantOwnershipVerifier` de la Fase 0.3-B, aplicado sin crear un servicio nuevo porque aquí basta una comparación de un campo.

### 3.5 Rutas

`src/SupportTicket/Infrastructure/Http/Routes/web.php`: el grupo `api/support` queda bajo `Route::middleware('support_session')`. Las vistas (`/account/support`, `/tenant/owner/backoffice/{uuid}/support`) no cambian — la segunda ya tenía `auth`, la primera se queda pública porque ahora resuelve la identidad de forma segura y devuelve una página vacía si no hay sesión, en vez de fallar o filtrar datos ajenos.

`src/SupportTicket/Infrastructure/Http/Routes/tenant.php` (contexto de dominio de tenant) no se toca: ya tenía `auth` en todo el grupo API desde antes.

---

## 4. Qué cierra esta fase

| Hallazgo | Estado |
| :--- | :--- |
| **A6** — mesa de soporte central abierta (lectura) | ✅ Cerrado |
| **A6** — suplantación de agentes vía `sender_type` | ✅ Cerrado |
| Escritura/cierre de tickets ajenos (nuevo) | ✅ Cerrado |
| `auth('central_customer')` muerto / 500 (parcial de **F4**) | 🟡 Parcial: resuelto sólo dentro de `SupportTicket`. El resto de controladores de `CentralCustomer` que llaman a `auth('central_customer')` sigue pendiente — es la Fase 0.3-D. |
| `session('customer_id')` nunca escrita en `ViewCustomerSupportGETController` | ✅ Cerrado |

---

## 5. Tareas

- [x] Crear middleware `EnsureSupportRequesterIsAuthenticated` y alias `support_session`
- [x] Crear trait `ResolvesSupportRequester`
- [x] Aplicar el trait en los 5 controladores públicos + `ViewCustomerSupportGETController`
- [x] Verificación de propiedad en `AddMessageToTicketUseCase` y `UpdateTicketStatusUseCase`
- [x] Propagar `requesterId` desde `AddTenantStoreSupportMessagePOSTController` (contexto de tienda)
- [x] Envolver el grupo `api/support` con `support_session`
- [x] Actualizar `tests/Feature/SupportTicket/SupportTicketLifecycleTest.php` (clave de sesión correcta + casos negativos nuevos)
- [x] `php artisan test --filter=SupportTicketLifecycleTest` — verde
- [x] `php artisan test --filter=TenantStoreSupportApiTest` — verde
- [ ] `php artisan route:clear && php artisan config:clear`
- [ ] `php artisan test` (suite completa, para descartar efectos secundarios en otros módulos)
- [ ] `npm run types`
- [ ] `vendor/bin/pint src/SupportTicket/ src/Shared/Infrastructure/Http/Middleware/`
- [ ] Commit: `fix(support): exigir sesión y verificar propiedad del ticket en la mesa de soporte central`
- [ ] `git push origin <rama_actual>`
- [ ] Mover este documento a `planes/implementados/`

---

## 6. Verificación manual

**Debe seguir funcionando:**
1. Un cliente con sesión (`central_customer_id`) crea un ticket, lo lista, responde y lo cierra desde `/account/support`.
2. Un propietario de tienda hace lo mismo desde `/tenant/owner/backoffice/{uuid}/support`.
3. El staff responde y cambia estado desde el panel de administración (`AdminReplySupportTicketPOSTController`).

**Debe dejar de funcionar:**
4. Sin sesión, `GET /api/support/tickets` → **401** (antes: 400 o el listado de quien fuera el `user_id` pasado).
5. Con sesión de cliente A, `GET /api/support/tickets/{ticket de B}` → **404** (el filtro por dueño ya lo oculta, igual que antes, pero ahora el `user_id` no se puede falsear).
6. Con sesión de cliente A, `POST /api/support/tickets/{ticket de B}/messages` → **403**.
7. Con sesión de cliente A, `PATCH /api/support/tickets/{ticket de B}/status` → **403**.
8. Con sesión de cliente A, enviar `sender_type: admin` en un mensaje a su propio ticket → se guarda igual como `sender_type: customer`.

---

## 7. Riesgo

**Medio.** Los flujos legítimos de cliente y propietario no cambian de forma de uso (siguen sin mandar `user_id` explícito en la mayoría de las llamadas del frontend actual, y si lo mandan, ahora simplemente se ignora). El punto a vigilar: cualquier ticket cuyo `user_id` no coincida exactamente con la sesión que lo consulta pasará a dar 403/404 en vez de responder — si existieran tickets de prueba con `user_id` inconsistente en producción, convendría revisarlos antes de desplegar.

---

## 8. Trabajo de seguimiento identificado

1. **Fase 0.3-D** (API de clientes centrales) sigue pendiente: `auth('central_customer')` aparece también en `GetTenantCustomerSessionGETController` y otros controladores de `CentralCustomer`, y las rutas de `apiCentral.php` (hallazgo A3) siguen sin middleware. Requiere decidir el mecanismo de guard antes de tocarlas.
2. **`ListCustomerOrdersGETController` y hermanos** (`ListCustomerWishlistGETController`, `ListCustomerInvoicesGETController`, etc.) siguen resolviendo la identidad como `$request->input('customer_id', $request->header('X-Customer-Id', ''))` — el mismo patrón que este documento corrige en soporte. Deberían adoptar el mismo enfoque de sesión cuando se aborde la Fase 0.3-D.
3. **`UpdateTicketStatusUseCase`** no restringe qué transiciones de estado puede hacer un no-staff (por ejemplo, un cliente podría reabrir un ticket ya `closed` y volver a marcarlo `in_progress`). No es un problema de seguridad —ya es dueño del ticket— pero podría merecer una máquina de estados más estricta en la Fase 2.
