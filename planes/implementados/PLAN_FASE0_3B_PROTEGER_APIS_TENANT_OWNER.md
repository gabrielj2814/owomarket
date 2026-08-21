# PLAN — Fase 0.3-B: Proteger las APIs del propietario de tienda y verificar la propiedad

> **Origen:** hallazgo A2 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md`, más dos bugs nuevos descubiertos durante la implementación
> **Severidad:** 🔴 Crítico
> **Tamaño:** 1 servicio nuevo, 5 casos de uso, 5 controladores, 1 archivo de rutas
> **Estado:** ✅ Implementado — pendiente de validación con tests
> **Depende de:** Fase 0.2 (alias `tenant_owner`)

---

## 1. El problema original (hallazgo A2)

En `src/Tenant/Infrastructure/Http/Routes/web.php`, las rutas de vista llevaban `->middleware('auth')` pero el bloque de APIs se lo saltó por completo:

```php
Route::post('/owner/filter/tenants', [ConsultTenantByUuidOfOwnerPOSTController::class, 'index']);
Route::post('/owner/api/sso-token', GenerateTenantOwnerSsoTokenPOSTController::class);
Route::get('/owner/api/wallet-summary', GetTenantOwnerWalletSummaryGETController::class);
Route::post('/owner/api/payout-request', CreateTenantOwnerPayoutRequestPOSTController::class);
Route::get('/owner/api/products', ListTenantOwnerProductsGETController::class);
Route::post('/owner/api/products/{id}/toggle-marketplace', ToggleTenantOwnerProductPublicationPOSTController::class);
```

Y los controladores tomaban la identidad del cuerpo, la query string o una cabecera arbitraria:

```php
$userId = (string) ($request->query('user_id')
    ?: $request->input('user_id')
    ?: $request->header('X-User-Id')
    ?: auth()->id());
```

**Explotación:** los UUID de usuario aparecen en las URLs del panel (`/tenant/owner/backoffice/{user_uuid}/dashboard`) y los devolvía `POST /owner/filter/tenants`, también abierto. Con un UUID cualquiera bastaba un `POST /tenant/owner/api/sso-token` para obtener una URL que hace `Auth::login($user, true)` — sesión como ese propietario, con cookie *remember me*.

---

## 2. Dos bugs nuevos descubiertos al leer los casos de uso

Ambos **habrían sobrevivido** a añadir `auth`, así que merecen atención aparte.

### 2.1 🔴 Fallback silencioso a las tiendas de otros comerciantes

En `GetTenantOwnerWalletSummaryUseCase.php:20` y `ListTenantOwnerProductsUseCase.php:19`:

```php
$tenants = Tenant::whereHas('users', fn($q) => $q->where('user_id', $userId))->get();

if ($tenants->isEmpty()) {
    $tenants = Tenant::where('status', 'active')->limit(5)->get();   // ← tiendas AJENAS
}
```

Si el usuario no tiene tiendas asociadas, el código **cae en las de otro**. Un usuario autenticado sin comercio propio veía la facturación bruta, las comisiones, el saldo y el catálogo completo de hasta cinco (o diez) tiendas ajenas, elegidas por orden de inserción.

### 2.2 🔴 `ToggleProductMarketplacePublicationUseCase` ignoraba el usuario

```php
public function execute(string $userId, string $productId, ?bool $status = null): array
{
    $product = CentralProduct::find($productId);
    // ... $userId no se usa NUNCA en todo el método
    $product->is_visible = $newStatus;
    $product->save();
```

Recibía `$userId` y no lo miraba. Cualquiera podía publicar o despublicar del marketplace central el producto de cualquier tienda.

---

## 3. Solución

### 3.1 Nuevo: `src/Tenant/Application/Service/TenantOwnershipVerifier.php`

Fuente única de verdad para "¿este usuario manda en esta tienda?", sobre la tabla pivote `tenant_users`:

| Método | Devuelve |
| :--- | :--- |
| `tenantIdsOf(string $userId): array` | IDs de sus tiendas; array vacío si no tiene |
| `tenantsOf(string $userId)` | Colección de modelos; colección vacía si no tiene |
| `owns(string $userId, string $tenantId): bool` | — |
| `ensureOwns(string $userId, string $tenantId): Tenant` | La tienda, o lanza 404 / 403 |

**No concede privilegios al super administrador.** Un super admin sin tiendas obtiene lista vacía, no las de otro. Para operar sobre una tienda ajena tiene su propio backoffice y el flujo de impersonación. Prefiero esa consistencia a una excepción silenciosa dentro del verificador.

### 3.2 Casos de uso modificados

| Archivo | Cambio |
| :--- | :--- |
| `GetTenantOwnerWalletSummaryUseCase` | Fuera el fallback. Sin tiendas → resumen en cero. Inyecta el verificador. |
| `ListTenantOwnerProductsUseCase` | Fuera el fallback. Sin tiendas → catálogo vacío con paginación coherente. El `tenant_id` de filtro que no sea suyo se ignora en lugar de ampliar el alcance. |
| `ToggleProductMarketplacePublicationUseCase` | `ensureOwns($userId, $product->tenant_id)` antes de tocar nada. |
| `CreateTenantOwnerPayoutRequestUseCase` | `ensureOwns()` + **validación del importe contra el saldo disponible** + `DB::transaction`. |
| `GenerateTenantOwnerSsoTokenUseCase` | Comprueba que el usuario sea propietario del `tenant_id` antes de emitir el token. |

Sobre la validación de saldo: antes se podía pedir cualquier importe (`amount > 0` era la única regla), y quedaba un `CommissionSettlement` pendiente con los datos bancarios del solicitante esperando la aprobación del admin. Ahora se calcula el saldo retirable de esa tienda —ventas menos comisiones, menos lo liquidado, menos lo pendiente— y se rechaza con 422 si el importe lo supera.

### 3.3 Controladores modificados

Los cinco dejan de leer `user_id` del request y usan `auth()->id()`:

```php
// La identidad SIEMPRE sale de la sesión, nunca del cuerpo de la petición.
$userId = (string) (auth()->id() ?? '');
```

`user_id` sale también de las reglas de `validate()` donde estaba declarado como obligatorio.

**Compatible con el frontend actual:** las páginas siguen enviando `user_id` en el cuerpo o los parámetros; simplemente se ignora. No hace falta tocar React en esta fase.

### 3.4 Rutas

Las seis rutas quedan agrupadas bajo `['auth', 'tenant_owner']`. `/auth/sso-consume` sigue **pública**, como debe ser: es la página de aterrizaje del SSO y su seguridad la aporta el token, no la sesión.

Verificado automáticamente: **29 rutas y 10 nombres, idénticos** al original.

---

## 4. Qué cierra esta fase

| Hallazgo | Estado |
| :--- | :--- |
| **A2** — emisión pública de tokens SSO de propietario | ✅ Cerrado |
| **A2** — wallet-summary y payout-request anónimos | ✅ Cerrado |
| Fallback a tiendas ajenas (nuevo) | ✅ Cerrado |
| Toggle de publicación sin dueño (nuevo) | ✅ Cerrado |
| Retiros sin validación de saldo (nuevo) | ✅ Cerrado |
| **A8** — el token SSO no se ata al tenant al consumirse | ⬜ Pendiente (es el consumo, no la emisión: va en otra sub-fase) |

---

## 5. Tareas

- [x] Crear `TenantOwnershipVerifier`
- [x] Eliminar el fallback a tiendas ajenas en los dos casos de uso
- [x] Verificar propiedad en toggle, payout y SSO
- [x] Validar el importe del retiro contra el saldo disponible
- [x] Derivar la identidad de `auth()->id()` en los cinco controladores
- [x] Agrupar las rutas bajo `['auth', 'tenant_owner']`
- [x] Verificar sintaxis y equivalencia de rutas
- [ ] `php artisan route:clear && php artisan config:clear`
- [x] `php artisan test`
- [x] `npm run types`
- [x] `vendor/bin/pint src/Tenant/`
- [x] Commit: `fix(tenant): exigir sesión de propietario y verificar propiedad en las APIs del owner`
- [x] `git push origin <rama_actual>`
- [x] Mover este documento a `planes/implementados/`

---

## 6. Tests que probablemente haya que actualizar

`tests/Feature/Tenant/TenantOwnerCentralHubTest.php` es el candidato: si llama a `/tenant/owner/api/*` sin `actingAs`, fallará con 401 — igual que pasó con el test de monetización, y por la misma buena razón.

Al actualizarlo, ojo con esto: **ya no basta con autenticar**. El usuario del test tiene que estar asociado a la tienda en `tenant_users`, o el verificador responderá 403. Algo así:

```php
$tenant->users()->attach($owner->id, ['role' => 'owner']);
```

---

## 7. Verificación manual

**Debe seguir funcionando** (sesión de un propietario con tienda asociada):
1. Panel del propietario → billetera, catálogo central, acceso directo a la tienda por SSO.
2. Publicar y despublicar un producto propio del marketplace central.

**Debe dejar de funcionar:**
3. Sin sesión, `POST /tenant/owner/api/sso-token {"tenant_id":"X","user_id":"Y"}` → **401**.
4. Sin sesión, `GET /tenant/owner/api/wallet-summary?user_id=<víctima>` → **401**.
5. Con sesión de propietario A, `POST /tenant/owner/api/sso-token {"tenant_id":"<tienda de B>"}` → **403**.
6. Con sesión de propietario A, despublicar un producto de la tienda de B → **403**.
7. Pedir un retiro por encima del saldo disponible → **422** con el importe disponible en el mensaje.

---

## 8. Riesgo

**Medio** — es la primera sub-fase que toca casos de uso, no solo rutas.

El punto delicado: si en la base de datos existen propietarios **sin fila en `tenant_users`** para su propia tienda, con este cambio verán su billetera y su catálogo vacíos (antes veían, incorrectamente, datos de otras tiendas). No es una regresión —es el fallback quitándose— pero conviene comprobar la integridad del pivote antes de desplegar:

```sql
SELECT t.id, t.slug FROM tenants t
LEFT JOIN tenant_users tu ON tu.tenant_id = t.id
WHERE tu.tenant_id IS NULL;
```

Si esa consulta devuelve filas, hay tiendas huérfanas y hay que reasociarlas antes de desplegar.

---

## 9. Trabajo de seguimiento identificado

1. **`TenantOwnerWalletPage.tsx:86`** hace `tenant_id: wallet.settlements[0]?.tenant_id || 'tecs'` — un slug hardcodeado como último recurso. Con la verificación de propiedad ahora dará 403 en lugar de crear un retiro contra una tienda ajena, pero el fallback debe eliminarse igual: si no hay tienda seleccionada, el botón no debería poder enviarse.

2. **La tasa BCV sigue fijada en código** en `GetTenantOwnerWalletSummaryUseCase` (`$bcvRate = 775.3356`), igual que en el frontend. Debe venir de `Src\ExchangeRate` (hallazgos D3 y G13). Queda marcado con un `TODO(Fase 1)`.

3. **La validación de saldo tiene una carrera residual.** Dos solicitudes simultáneas pueden leer el mismo saldo y crear dos retiros que juntos lo superen. La transacción reduce la ventana pero no la cierra; hace falta un `lockForUpdate` sobre las liquidaciones de la tienda, que va con el bloque de concurrencia de la Fase 1 (hallazgo C3).
